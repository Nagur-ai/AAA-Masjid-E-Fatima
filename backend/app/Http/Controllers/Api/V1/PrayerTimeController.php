<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Masjid;
use App\Services\PrayerTimes\DailySchedule;
use App\Services\PrayerTimes\PrayerScheduleService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PrayerTimeController extends Controller
{
    public function __construct(private readonly PrayerScheduleService $schedules) {}

    /**
     * Times for one day (default today in the masjid's timezone), with the next upcoming prayer.
     */
    public function daily(Request $request, Masjid $masjid): JsonResponse
    {
        $request->validate(['date' => ['nullable', 'date_format:Y-m-d']]);

        $now = CarbonImmutable::now($masjid->timezone);
        $date = $request->filled('date') ? CarbonImmutable::parse($request->date, $masjid->timezone) : $now;
        $schedule = $this->schedules->forDate($masjid, $date);

        $next = $schedule->nextPrayer($now);
        if ($next === null && $date->isSameDay($now)) {
            $tomorrow = $this->schedules->forDate($masjid, $now->addDay());
            $next = $tomorrow->nextPrayer($now);
        }

        return response()->json([
            'masjid' => ['slug' => $masjid->slug, 'name' => $masjid->name, 'timezone' => $masjid->timezone],
            'method' => $masjid->calculation_method->label(),
            // Server time in the masjid's timezone; clients use its UTC offset for a live local clock.
            'now' => $now->toIso8601String(),
            'data' => $schedule->toArray(),
            'next_prayer' => $next ? ['prayer' => $next['prayer'], 'at' => $next['at']->toIso8601String()] : null,
        ])->setCache(['public' => true, 'max_age' => 300]);
    }

    public function monthly(Request $request, Masjid $masjid): JsonResponse
    {
        $now = CarbonImmutable::now($masjid->timezone);
        $params = $request->validate([
            'year' => ['nullable', 'integer', 'between:1900,2200'],
            'month' => ['nullable', 'integer', 'between:1,12'],
        ]);
        $year = (int) ($params['year'] ?? $now->year);
        $month = (int) ($params['month'] ?? $now->month);

        return response()->json([
            'masjid' => ['slug' => $masjid->slug, 'name' => $masjid->name, 'timezone' => $masjid->timezone],
            'method' => $masjid->calculation_method->label(),
            'year' => $year,
            'month' => $month,
            'data' => array_map(fn (DailySchedule $d) => $d->toArray(), $this->schedules->forMonth($masjid, $year, $month)),
        ])->setCache(['public' => true, 'max_age' => 3600]);
    }
}
