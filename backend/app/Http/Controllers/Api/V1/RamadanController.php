<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateRamadanRequest;
use App\Models\Masjid;
use App\Services\Ramadan\RamadanCalendarService;
use Illuminate\Http\JsonResponse;

class RamadanController extends Controller
{
    public function __construct(private readonly RamadanCalendarService $ramadan) {}

    /**
     * Sehri/iftar timetable for a Hijri year (default: the current or next Ramadan).
     */
    public function show(Masjid $masjid, ?int $hijriYear = null): JsonResponse
    {
        $hijriYear ??= $this->ramadan->upcomingYear($masjid);
        abort_unless($hijriYear >= 1400 && $hijriYear <= 1600, 404);

        return response()->json(['data' => $this->ramadan->build($masjid, $hijriYear)]);
    }

    /**
     * Record the announced (moon-sighted) start of Ramadan.
     */
    public function update(UpdateRamadanRequest $request, Masjid $masjid, int $hijriYear): JsonResponse
    {
        $masjid->ramadanCalendars()->updateOrCreate(['hijri_year' => $hijriYear], $request->validated());

        return response()->json(['data' => $this->ramadan->build($masjid, $hijriYear)]);
    }
}
