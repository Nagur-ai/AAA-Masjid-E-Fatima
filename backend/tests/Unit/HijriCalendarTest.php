<?php

namespace Tests\Unit;

use App\Services\Hijri\HijriCalendar;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class HijriCalendarTest extends TestCase
{
    public function test_converts_known_dates(): void
    {
        $hijri = (new HijriCalendar)->fromGregorian(CarbonImmutable::parse('2025-03-01'));

        $this->assertSame([1446, 9, 1], [$hijri->year, $hijri->month, $hijri->day]);
        $this->assertSame('1 Ramadan 1446 AH', $hijri->format());
    }

    public function test_round_trips_ramadan_start(): void
    {
        $calendar = new HijriCalendar;
        $start = $calendar->toGregorian(1447, 9, 1);

        $this->assertSame('2026-02-18', $start->toDateString());
        $back = $calendar->fromGregorian($start);
        $this->assertSame([1447, 9, 1], [$back->year, $back->month, $back->day]);
    }

    public function test_adjustment_shifts_by_whole_days(): void
    {
        $calendar = new HijriCalendar;

        $this->assertSame(2, $calendar->fromGregorian(CarbonImmutable::parse('2025-03-01'), 1)->day);
        $this->assertSame('2026-02-19', $calendar->toGregorian(1447, 9, 1, -1)->toDateString());
    }

    public function test_month_length_is_29_or_30(): void
    {
        $this->assertContains((new HijriCalendar)->monthLength(1448, 9), [29, 30]);
    }
}
