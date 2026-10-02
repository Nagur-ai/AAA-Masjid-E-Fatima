<?php

namespace Tests\Unit;

use App\Enums\AsrMethod;
use App\Enums\CalculationMethod;
use App\Enums\HighLatitudeRule;
use App\Services\PrayerTimes\PrayerTimeCalculator;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PrayerTimeCalculatorTest extends TestCase
{
    /**
     * Reference values from api.aladhan.com for 2 Oct 2026 with the same parameters.
     */
    public static function referenceTimes(): array
    {
        return [
            'Hyderabad, Karachi, Hanafi' => [
                17.385, 78.4867, 'Asia/Kolkata', CalculationMethod::Karachi, AsrMethod::Hanafi,
                ['fajr' => '04:54', 'sunrise' => '06:06', 'dhuhr' => '12:05', 'asr' => '16:25', 'maghrib' => '18:04', 'isha' => '19:16'],
            ],
            'London, MWL, Standard' => [
                51.5074, -0.1278, 'Europe/London', CalculationMethod::MWL, AsrMethod::Standard,
                ['fajr' => '05:11', 'sunrise' => '07:03', 'dhuhr' => '12:50', 'asr' => '15:56', 'maghrib' => '18:36', 'isha' => '20:21'],
            ],
        ];
    }

    #[DataProvider('referenceTimes')]
    public function test_matches_reference_times_within_a_minute(float $lat, float $lng, string $tz, CalculationMethod $method, AsrMethod $asr, array $expected): void
    {
        $times = (new PrayerTimeCalculator($method, $asr))->calculate(CarbonImmutable::parse('2026-10-02'), $lat, $lng, $tz);

        foreach ($expected as $name => $hhmm) {
            $actual = $times[$name];
            $this->assertSame('2026-10-02', $actual->toDateString(), "$name falls on the wrong day");
            $reference = CarbonImmutable::parse("2026-10-02 $hhmm", $tz);
            $this->assertLessThanOrEqual(1, abs($actual->diffInMinutes($reference)), "$name: got {$actual->format('H:i')}, expected $hhmm");
        }
    }

    public function test_times_are_in_chronological_order(): void
    {
        $times = (new PrayerTimeCalculator)->calculate(CarbonImmutable::parse('2026-03-15'), 40.7128, -74.006, 'America/New_York');
        $order = ['imsak', 'fajr', 'sunrise', 'dhuhr', 'asr', 'sunset', 'isha', 'midnight'];

        for ($i = 1; $i < count($order); $i++) {
            $this->assertTrue($times[$order[$i]]->greaterThan($times[$order[$i - 1]]), "{$order[$i]} should follow {$order[$i - 1]}");
        }
    }

    public function test_daylight_saving_shift_is_applied(): void
    {
        $calc = new PrayerTimeCalculator;
        // UK clocks go forward on 29 March 2026.
        $before = $calc->calculate(CarbonImmutable::parse('2026-03-28'), 51.5074, -0.1278, 'Europe/London')['dhuhr'];
        $after = $calc->calculate(CarbonImmutable::parse('2026-03-29'), 51.5074, -0.1278, 'Europe/London')['dhuhr'];

        // Solar noon moves by seconds a day, so the wall-clock time jumps by an hour.
        $clockMinutes = fn ($t) => $t->hour * 60 + $t->minute;
        $this->assertEqualsWithDelta(60, $clockMinutes($after) - $clockMinutes($before), 1);
        $this->assertSame('+01:00', $after->format('P'));
    }

    public function test_high_latitude_rule_fills_in_missing_twilight(): void
    {
        $date = CarbonImmutable::parse('2026-06-21');

        $without = (new PrayerTimeCalculator(highLatitude: HighLatitudeRule::None))->calculate($date, 57.0, -2.0, 'Europe/London');
        $with = (new PrayerTimeCalculator(highLatitude: HighLatitudeRule::AngleBased))->calculate($date, 57.0, -2.0, 'Europe/London');

        $this->assertNull($without['fajr'], 'At 57°N in midsummer the sun never reaches 18° below the horizon');
        $this->assertNotNull($with['fajr']);
        $this->assertTrue($with['fajr']->lessThan($with['sunrise']));
    }

    public function test_fixed_minute_isha_and_imsak_offset(): void
    {
        $times = (new PrayerTimeCalculator(CalculationMethod::Makkah, imsakMinutes: 10))
            ->calculate(CarbonImmutable::parse('2026-10-02'), 21.4225, 39.8262, 'Asia/Riyadh');

        $this->assertSame(90, (int) $times['maghrib']->diffInMinutes($times['isha']));
        $this->assertSame(10, (int) $times['imsak']->diffInMinutes($times['fajr']));
    }
}
