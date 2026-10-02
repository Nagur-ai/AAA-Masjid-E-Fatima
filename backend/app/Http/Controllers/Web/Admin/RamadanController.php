<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateRamadanRequest;
use App\Services\Ramadan\RamadanCalendarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RamadanController extends Controller
{
    public function __construct(private readonly RamadanCalendarService $ramadan) {}

    public function edit(?int $hijriYear = null): View
    {
        $masjid = $this->masjid();
        $hijriYear ??= $this->ramadan->upcomingYear($masjid);
        abort_unless($hijriYear >= 1400 && $hijriYear <= 1600, 404);

        return view('admin.ramadan.edit', [
            'hijriYear' => $hijriYear,
            'calendar' => $this->ramadan->calendarFor($masjid, $hijriYear),
        ]);
    }

    public function update(UpdateRamadanRequest $request, int $hijriYear): RedirectResponse
    {
        $this->masjid()->ramadanCalendars()->updateOrCreate(
            ['hijri_year' => $hijriYear],
            [...$request->validated(), 'is_confirmed' => $request->boolean('is_confirmed')],
        );

        return back()->with('status', "Ramadan {$hijriYear} calendar saved.");
    }
}
