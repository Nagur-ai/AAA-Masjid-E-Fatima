<?php

namespace App\Services\PrayerTimes;

use App\Enums\Prayer;
use App\Models\IqamahRule;
use App\Models\Masjid;
use App\Models\PrayerTimeOverride;
use App\Services\Hijri\HijriCalendar;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

/**
 * Combines calculated adhan times with the masjid's iqamah rules and Jumuah sessions,
 * then applies any adhan/iqamah times the admin has set by hand for a date.
 */
class PrayerScheduleService
{
    public function __construct(private readonly HijriCalendar $hijri) {}

    public function forDate(Masjid $masjid, CarbonInterface $date, bool $withOverrides = true): DailySchedule
    {
        return $this->forRange($masjid, $date, $date, $withOverrides)[0];
    }

    /**
     * @return list<DailySchedule>
     */
    public function forMonth(Masjid $masjid, int $year, int $month): array
    {
        $start = CarbonImmutable::create($year, $month, 1, 0, 0, 0, $masjid->timezone);

        return $this->forRange($masjid, $start, $start->endOfMonth());
    }

    /**
     * @return list<DailySchedule>
     */
    public function forRange(Masjid $masjid, CarbonInterface $from, CarbonInterface $to, bool $withOverrides = true): array
    {
        $from = CarbonImmutable::parse($from->toDateString(), $masjid->timezone);
        $to = CarbonImmutable::parse($to->toDateString(), $masjid->timezone);

        $calculator = new PrayerTimeCalculator(
            $masjid->calculation_method,
            $masjid->asr_method,
            $masjid->high_latitude_rule,
            $masjid->imsak_minutes_before_fajr,
        );

        // Load every rule overlapping the range once instead of querying per day.
        $rules = $masjid->iqamahRules()
            ->whereDate('effective_from', '<=', $to->toDateString())
            ->where(fn ($q) => $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $from->toDateString()))
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->get();

        $jumuah = $masjid->jumuahSessions->map(fn ($s) => [
            'label' => $s->label,
            'khutbah' => substr($s->khutbah_time, 0, 5),
            'iqamah' => substr($s->iqamah_time, 0, 5),
            'language' => $s->language,
        ])->all();

        // The admin's own times, keyed by date then prayer.
        $overrides = ($withOverrides && $masjid->exists)
            ? $masjid->prayerTimeOverrides()
                ->whereDate('date', '>=', $from->toDateString())->whereDate('date', '<=', $to->toDateString())
                ->get()->groupBy(fn (PrayerTimeOverride $o) => $o->date->toDateString())
            : collect();

        $days = [];
        foreach (CarbonPeriod::create($from, $to) as $day) {
            $day = CarbonImmutable::instance($day);
            $times = $calculator->calculate($day, $masjid->latitude, $masjid->longitude, $masjid->timezone, $masjid->elevation);
            $set = $overrides->get($day->toDateString(), collect())->keyBy(fn (PrayerTimeOverride $o) => $o->prayer->value);

            // A changed adhan also moves a rule-based iqamah (e.g. "15 minutes after adhan").
            foreach ($set as $prayer => $o) {
                if ($o->adhan) {
                    $times[$prayer] = $this->at($day, $o->adhan);
                }
            }
            $iqamah = $this->iqamahFor($rules, $day, $times);
            foreach ($set as $prayer => $o) {
                if ($o->iqamah) {
                    $iqamah[$prayer] = $this->at($day, $o->iqamah);
                }
            }

            $days[] = new DailySchedule(
                date: $day,
                hijri: $this->hijri->fromGregorian($day, $masjid->hijri_adjustment),
                times: $times,
                iqamah: $iqamah,
                jumuah: $day->isFriday() ? $jumuah : [],
            );
        }

        return $days;
    }

    /**
     * @param  Collection<int, IqamahRule>  $rules  Sorted newest-effective first
     * @param  array<string, CarbonImmutable|null>  $times
     * @return array<string, CarbonImmutable|null>
     */
    private function iqamahFor(Collection $rules, CarbonImmutable $day, array $times): array
    {
        $iqamah = [];
        $date = $day->toDateString();

        foreach (Prayer::cases() as $prayer) {
            $adhan = $times[$prayer->value];
            $rule = $rules->first(fn (IqamahRule $r) => $r->prayer === $prayer
                && $r->effective_from->toDateString() <= $date
                && ($r->effective_to === null || $r->effective_to->toDateString() >= $date));

            $iqamah[$prayer->value] = ($adhan && $rule) ? $rule->resolve($adhan) : null;
        }

        return $iqamah;
    }

    /**
     * "HH:MM[:SS]" on the given day, in the masjid's timezone.
     */
    private function at(CarbonImmutable $day, string $time): CarbonImmutable
    {
        return $day->setTimeFromTimeString(substr($time, 0, 5));
    }
}
