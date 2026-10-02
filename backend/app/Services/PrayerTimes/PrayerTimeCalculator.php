<?php

namespace App\Services\PrayerTimes;

use App\Enums\AsrMethod;
use App\Enums\CalculationMethod;
use App\Enums\HighLatitudeRule;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Astronomical prayer-time calculation, ported from the PrayTimes.org algorithm (v2.3).
 *
 * Times are solved in UTC (timezone offset 0) and then converted to the masjid's
 * timezone, so daylight-saving transitions are handled by PHP's tz database.
 */
final class PrayerTimeCalculator
{
    public const TIMES = ['imsak', 'fajr', 'sunrise', 'dhuhr', 'asr', 'sunset', 'maghrib', 'isha', 'midnight'];

    private float $jDate;

    private float $lat;

    public function __construct(
        private readonly CalculationMethod $method = CalculationMethod::MWL,
        private readonly AsrMethod $asr = AsrMethod::Standard,
        private readonly HighLatitudeRule $highLatitude = HighLatitudeRule::AngleBased,
        private readonly int $imsakMinutes = 10,
    ) {}

    /**
     * @return array<string, CarbonImmutable|null> Keyed by self::TIMES. Null when the sun
     *                                             never reaches the required angle and no
     *                                             high-latitude rule applies.
     */
    public function calculate(CarbonInterface $date, float $latitude, float $longitude, string $timezone, int $elevation = 0): array
    {
        $this->lat = $latitude;
        $this->jDate = $this->julian($date->year, $date->month, $date->day) - $longitude / (15 * 24);

        $hours = $this->adjust($this->computeTimes($elevation), $longitude);

        $midnightUtc = CarbonImmutable::create($date->year, $date->month, $date->day, 0, 0, 0, 'UTC');

        $result = [];
        foreach (self::TIMES as $name) {
            $value = $hours[$name];
            $result[$name] = is_nan($value)
                ? null
                : $midnightUtc->addMinutes((int) round($value * 60))->setTimezone($timezone);
        }

        return $result;
    }

    /**
     * @return array<string, float> Hours (UTC-solar, before longitude adjustment).
     */
    private function computeTimes(int $elevation): array
    {
        $p = $this->method->parameters();
        $riseSet = 0.833 + 0.0347 * sqrt($elevation);

        // Initial guesses as fractions of a day; one refinement pass is sufficient.
        $t = ['fajr' => 5 / 24, 'sunrise' => 6 / 24, 'dhuhr' => 12 / 24, 'asr' => 13 / 24, 'sunset' => 18 / 24, 'maghrib' => 18 / 24, 'isha' => 18 / 24];

        return [
            'fajr' => $this->sunAngleTime($p['fajr'], $t['fajr'], true),
            'sunrise' => $this->sunAngleTime($riseSet, $t['sunrise'], true),
            'dhuhr' => $this->midDay($t['dhuhr']),
            'asr' => $this->asrTime($this->asr->shadowFactor(), $t['asr']),
            'sunset' => $this->sunAngleTime($riseSet, $t['sunset']),
            'maghrib' => $p['maghrib'] !== null ? $this->sunAngleTime($p['maghrib'], $t['maghrib']) : NAN,
            'isha' => $p['isha_minutes'] === null ? $this->sunAngleTime($p['isha'], $t['isha']) : NAN,
        ];
    }

    /**
     * @param  array<string, float>  $times
     * @return array<string, float>
     */
    private function adjust(array $times, float $longitude): array
    {
        $p = $this->method->parameters();

        foreach ($times as $name => $value) {
            $times[$name] = $value - $longitude / 15;
        }

        if ($p['maghrib'] === null) {
            $times['maghrib'] = $times['sunset'];
        }

        if ($this->highLatitude !== HighLatitudeRule::None) {
            $night = $this->timeDiff($times['sunset'], $times['sunrise']);
            $times['fajr'] = $this->adjustHighLatitude($times['fajr'], $times['sunrise'], $p['fajr'], $night, true);
            if ($p['isha_minutes'] === null) {
                $times['isha'] = $this->adjustHighLatitude($times['isha'], $times['sunset'], $p['isha'], $night, false);
            }
            if ($p['maghrib'] !== null) {
                $times['maghrib'] = $this->adjustHighLatitude($times['maghrib'], $times['sunset'], $p['maghrib'], $night, false);
            }
        }

        if ($p['isha_minutes'] !== null) {
            $times['isha'] = $times['maghrib'] + $p['isha_minutes'] / 60;
        }

        $times['imsak'] = $times['fajr'] - $this->imsakMinutes / 60;

        $nightEnd = $p['midnight_from_fajr'] ? $times['fajr'] : $times['sunrise'];
        $times['midnight'] = $times['sunset'] + $this->timeDiff($times['sunset'], $nightEnd) / 2;

        return $times;
    }

    private function adjustHighLatitude(float $time, float $base, float $angle, float $night, bool $beforeBase): float
    {
        $portion = match ($this->highLatitude) {
            HighLatitudeRule::MiddleOfNight => 1 / 2,
            HighLatitudeRule::OneSeventh => 1 / 7,
            default => $angle / 60,
        } * $night;

        $diff = $beforeBase ? $this->timeDiff($time, $base) : $this->timeDiff($base, $time);

        if (is_nan($time) || $diff > $portion) {
            return $base + ($beforeBase ? -$portion : $portion);
        }

        return $time;
    }

    private function midDay(float $time): float
    {
        $eqt = $this->sunPosition($this->jDate + $time)['equation'];

        return $this->fixHour(12 - $eqt);
    }

    /**
     * Time at which the sun is $angle degrees below the horizon; before noon when $beforeNoon.
     */
    private function sunAngleTime(float $angle, float $time, bool $beforeNoon = false): float
    {
        $decl = $this->sunPosition($this->jDate + $time)['declination'];
        $noon = $this->midDay($time);
        $cos = (-$this->sin($angle) - $this->sin($decl) * $this->sin($this->lat))
            / ($this->cos($decl) * $this->cos($this->lat));

        if ($cos < -1 || $cos > 1) {
            return NAN;
        }

        $t = rad2deg(acos($cos)) / 15;

        return $noon + ($beforeNoon ? -$t : $t);
    }

    private function asrTime(int $factor, float $time): float
    {
        $decl = $this->sunPosition($this->jDate + $time)['declination'];
        $angle = -rad2deg(atan(1 / ($factor + tan(deg2rad(abs($this->lat - $decl))))));

        return $this->sunAngleTime($angle, $time);
    }

    /**
     * @return array{declination: float, equation: float}
     */
    private function sunPosition(float $jd): array
    {
        $d = $jd - 2451545.0;
        $g = $this->fixAngle(357.529 + 0.98560028 * $d);
        $q = $this->fixAngle(280.459 + 0.98564736 * $d);
        $l = $this->fixAngle($q + 1.915 * $this->sin($g) + 0.020 * $this->sin(2 * $g));
        $e = 23.439 - 0.00000036 * $d;

        $ra = rad2deg(atan2($this->cos($e) * $this->sin($l), $this->cos($l))) / 15;

        return [
            'declination' => rad2deg(asin($this->sin($e) * $this->sin($l))),
            'equation' => $q / 15 - $this->fixHour($ra),
        ];
    }

    private function julian(int $year, int $month, int $day): float
    {
        if ($month <= 2) {
            $year--;
            $month += 12;
        }
        $a = floor($year / 100);
        $b = 2 - $a + floor($a / 4);

        return floor(365.25 * ($year + 4716)) + floor(30.6001 * ($month + 1)) + $day + $b - 1524.5;
    }

    private function timeDiff(float $from, float $to): float
    {
        return $this->fixHour($to - $from);
    }

    private function fixAngle(float $a): float
    {
        return $a - 360 * floor($a / 360);
    }

    private function fixHour(float $h): float
    {
        return $h - 24 * floor($h / 24);
    }

    private function sin(float $deg): float
    {
        return sin(deg2rad($deg));
    }

    private function cos(float $deg): float
    {
        return cos(deg2rad($deg));
    }
}
