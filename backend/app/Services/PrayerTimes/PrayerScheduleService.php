<?php

namespace App\Services\PrayerTimes;

use App\Enums\Prayer;
use App\Models\IqamahRule;
use App\Models\Masjid;
use App\Services\Hijri\HijriCalendar;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

/**
 * Combines calculated adhan times with the masjid's iqamah rules and Jumuah sessions.
 */
class PrayerScheduleService
{
    public function __construct(private readonly HijriCalendar $hijri) {}

    public function forDate(Masjid $masjid, CarbonInterface $date): DailySchedule
    {
        return $this->forRange($masjid, $date, $date)[0];
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
    public function forRange(Masjid $masjid, CarbonInterface $from, CarbonInterface $to): array
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

        $days = [];
        foreach (CarbonPeriod::create($from, $to) as $day) {
            $day = CarbonImmutable::instance($day);
            $times = $calculator->calculate($day, $masjid->latitude, $masjid->longitude, $masjid->timezone, $masjid->elevation);

            $days[] = new DailySchedule(
                date: $day,
                hijri: $this->hijri->fromGregorian($day, $masjid->hijri_adjustment),
                times: $times,
                iqamah: $this->iqamahFor($rules, $day, $times),
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
}
