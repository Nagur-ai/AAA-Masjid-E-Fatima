<?php

namespace App\Services\Ramadan;

use App\Models\Masjid;
use App\Models\RamadanCalendar;
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

        $days = array_map(function (DailySchedule $s) use ($start) {
            $number = (int) $start->diffInDays($s->date) + 1;
            // The Islamic night begins at maghrib, so night N+1 follows iftar on day N.
            $nightNumber = $number + 1;
            $daily = $s->toArray();

            return [
                'day' => $number,
                'date' => $daily['date'],
                'weekday' => $daily['weekday'],
                'weekday_te' => $daily['weekday_te'],
                'hijri' => $daily['hijri'],
                'sehri_ends' => $daily['times']['fajr'],
                'imsak' => $daily['times']['imsak'],
                'iftar' => $daily['times']['maghrib'],
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
            'duas' => config('ramadan.duas'),
        ];
    }
}
