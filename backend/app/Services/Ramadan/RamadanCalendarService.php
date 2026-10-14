<?php

namespace App\Services\Ramadan;

use App\Models\Masjid;
use App\Models\RamadanCalendar;
use App\Models\RamadanDayTime;
use App\Services\Hijri\HijriCalendar;
use App\Services\PrayerTimes\DailySchedule;
use App\Services\PrayerTimes\PrayerScheduleService;
use Carbon\CarbonImmutable;

class RamadanCalendarService
{
    private const RAMADAN = 9;

    public function __construct(
        private readonly HijriCalendar $hijri,
        private readonly PrayerScheduleService $schedules,
    ) {}

    /**
     * The current Ramadan's year, or the next one once it has passed.
     */
    public function upcomingYear(Masjid $masjid, ?CarbonImmutable $today = null): int
    {
        $today ??= CarbonImmutable::now($masjid->timezone);
        $hijri = $this->hijri->fromGregorian($today, $masjid->hijri_adjustment);

        return $hijri->month > self::RAMADAN ? $hijri->year + 1 : $hijri->year;
    }

    /**
     * The stored calendar for the year, or an unsaved estimate from Umm al-Qura.
     */
    public function calendarFor(Masjid $masjid, int $hijriYear): RamadanCalendar
    {
        $stored = $masjid->ramadanCalendars()->where('hijri_year', $hijriYear)->first();
        if ($stored) {
            return $stored;
        }

        $estimate = new RamadanCalendar([
            'hijri_year' => $hijriYear,
            'starts_on' => $this->hijri->toGregorian($hijriYear, self::RAMADAN, 1, $masjid->hijri_adjustment)->toDateString(),
            'total_days' => $this->hijri->monthLength($hijriYear, self::RAMADAN),
            'is_confirmed' => false,
        ]);
        $estimate->masjid_id = $masjid->id;

        return $estimate;
    }

    public function build(Masjid $masjid, int $hijriYear): array
    {
        $calendar = $this->calendarFor($masjid, $hijriYear);
        $start = CarbonImmutable::parse($calendar->starts_on->toDateString(), $masjid->timezone);
        $end = $start->addDays($calendar->total_days - 1);

        $custom = $this->customTimes($masjid, $hijriYear);

        $days = array_map(function (DailySchedule $s) use ($start, $custom, $masjid) {
            $number = (int) $start->diffInDays($s->date) + 1;
            // The Islamic night begins at maghrib, so night N+1 follows iftar on day N.
            $nightNumber = $number + 1;
            $daily = $s->toArray();
            $set = $custom[$number] ?? null;
            $sehri = $set?->sehri ? substr($set->sehri, 0, 5) : null;
            $iftar = $set?->iftar ? substr($set->iftar, 0, 5) : null;
            // The admin may also move the day to another date and name the day by hand.
            $date = $set?->date ? CarbonImmutable::parse($set->date->toDateString(), $masjid->timezone) : $s->date;

            return [
                'day' => $number,
                'ashra' => min(3, intdiv($number - 1, 10) + 1),
                'date' => $date->toDateString(),
                'weekday' => $date->format('l'),
                'weekday_te' => $set?->day_label ?: $date->locale('te')->dayName,
                'date_calculated' => $daily['date'],
                'weekday_te_calculated' => $daily['weekday_te'],
                'date_custom' => (bool) $set?->date,
                'day_label_custom' => (bool) $set?->day_label,
                'hijri' => $daily['hijri'],
                'sehri_ends' => $sehri ?? $daily['times']['fajr'],
                'imsak' => $sehri ? $this->imsakBefore($sehri, $masjid) : $daily['times']['imsak'],
                'iftar' => $iftar ?? $daily['times']['maghrib'],
                'sehri_calculated' => $daily['times']['fajr'],
                'iftar_calculated' => $daily['times']['maghrib'],
                'sehri_custom' => $sehri !== null,
                'iftar_custom' => $iftar !== null,
                'times' => $daily['times'],
                'iqamah' => $daily['iqamah'],
                'is_last_ten' => $number >= 21,
                'odd_night_after_iftar' => ($nightNumber >= 21 && $nightNumber % 2 === 1) ? $nightNumber : null,
            ];
        }, $this->schedules->forRange($masjid, $start, $end));

        // The night after the final day belongs to Shawwal, not Ramadan.
        $days[count($days) - 1]['odd_night_after_iftar'] = null;

        return [
            'hijri_year' => $hijriYear,
            'starts_on' => $start->toDateString(),
            'ends_on' => $end->toDateString(),
            'total_days' => $calendar->total_days,
            'is_confirmed' => $calendar->is_confirmed,
            'taraweeh_time' => $calendar->taraweeh_time ? substr($calendar->taraweeh_time, 0, 5) : null,
            'notes' => $calendar->notes,
            'eid_al_fitr' => $end->addDay()->toDateString(),
            'days' => $days,
            'duas' => $masjid->ramadanDuas()->where('is_active', true)->get()->map->toPublicArray()->all(),
            'timetable_image_url' => $masjid->ramadan_image_path ? route('masjid.ramadan.image', $masjid) : null,
        ];
    }

    /**
     * Today's roza number with its sehri, iftar and imsak (the admin's times where set),
     * or null outside Ramadan.
     *
     * @return array{day: int, sehri: string|null, iftar: string|null, imsak: string|null}|null
     */
    public function today(Masjid $masjid, DailySchedule $schedule): ?array
    {
        $year = $this->upcomingYear($masjid, $schedule->date);
        $day = collect($this->build($masjid, $year)['days'])->firstWhere('date', $schedule->date->toDateString());

        return $day ? ['day' => $day['day'], 'sehri' => $day['sehri_ends'], 'iftar' => $day['iftar'], 'imsak' => $day['imsak']] : null;
    }

    /**
     * @return array<int, RamadanDayTime> keyed by roza number
     */
    private function customTimes(Masjid $masjid, int $hijriYear): array
    {
        if (! $masjid->exists) {
            return [];
        }

        return $masjid->ramadanDayTimes()->where('hijri_year', $hijriYear)->get()->keyBy('day')->all();
    }

    private function imsakBefore(string $sehri, Masjid $masjid): string
    {
        return CarbonImmutable::createFromFormat('H:i', $sehri)->subMinutes($masjid->imsak_minutes_before_fajr ?? 10)->format('H:i');
    }
}
