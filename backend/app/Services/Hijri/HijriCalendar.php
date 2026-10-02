<?php

namespace App\Services\Hijri;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use IntlCalendar;

/**
 * Gregorian <-> Hijri conversion using ICU's Umm al-Qura calendar.
 *
 * $adjustment shifts the Hijri date by whole days to follow local moon sighting:
 * +1 means the local Hijri date is one day ahead of Umm al-Qura.
 */
final class HijriCalendar
{
    public function fromGregorian(CarbonInterface $date, int $adjustment = 0): HijriDate
    {
        $calendar = $this->calendar();
        $calendar->setTime($this->noonUtc($date->year, $date->month, $date->day)->addDays($adjustment)->getTimestampMs());

        return new HijriDate(
            $calendar->get(IntlCalendar::FIELD_YEAR),
            $calendar->get(IntlCalendar::FIELD_MONTH) + 1,
            $calendar->get(IntlCalendar::FIELD_DAY_OF_MONTH),
        );
    }

    public function toGregorian(int $year, int $month, int $day, int $adjustment = 0): CarbonImmutable
    {
        $calendar = $this->calendar();
        $calendar->clear();
        $calendar->set($year, $month - 1, $day, 12, 0, 0);

        return CarbonImmutable::createFromTimestampMsUTC($calendar->getTime())
            ->startOfDay()
            ->subDays($adjustment);
    }

    public function monthLength(int $year, int $month): int
    {
        $calendar = $this->calendar();
        $calendar->clear();
        $calendar->set($year, $month - 1, 1, 12, 0, 0);

        return $calendar->getActualMaximum(IntlCalendar::FIELD_DAY_OF_MONTH);
    }

    private function calendar(): IntlCalendar
    {
        return IntlCalendar::createInstance('UTC', 'en_US@calendar=islamic-umalqura');
    }

    private function noonUtc(int $year, int $month, int $day): CarbonImmutable
    {
        return CarbonImmutable::create($year, $month, $day, 12, 0, 0, 'UTC');
    }
}
