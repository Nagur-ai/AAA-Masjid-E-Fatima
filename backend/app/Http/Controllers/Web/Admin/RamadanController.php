<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateRamadanRequest;
use App\Models\RamadanDayTime;
use App\Models\RamadanDua;
use App\Services\Ramadan\RamadanCalendarService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
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
            'timetable' => $this->ramadan->build($masjid, $hijriYear),
            'duas' => $masjid->ramadanDuas()->get(),
            'duaTypes' => RamadanDua::TYPES,
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

    /**
     * Save each day's date, Telugu day name and sehri/iftar times. A value equal to the
     * calculated one (or left empty) is not stored, so that field keeps following the calculation.
     */
    public function updateDays(Request $request, int $hijriYear): RedirectResponse
    {
        abort_unless($hijriYear >= 1400 && $hijriYear <= 1600, 404);
        $request->validate([
            'date' => ['array'], 'date.*' => ['nullable', 'date_format:Y-m-d'],
            'day_label' => ['array'],
            'day_label.*' => ['nullable', Rule::in([...RamadanDayTime::DAY_NAMES, ...RamadanDayTime::DAY_NAMES_SHORT])],
            'sehri' => ['array'], 'sehri.*' => ['nullable', 'date_format:H:i'],
            'iftar' => ['array'], 'iftar.*' => ['nullable', 'date_format:H:i'],
        ]);
        $masjid = $this->masjid();

        foreach ($this->ramadan->build($masjid, $hijriYear)['days'] as $d) {
            $n = $d['day'];
            $date = $request->input("date.{$n}");
            $date = ($date && $date !== $d['date_calculated']) ? $date : null;
            // A day name equal to the weekday of the (new) date is automatic, so not stored.
            $label = trim((string) $request->input("day_label.{$n}"));
            $autoLabel = CarbonImmutable::parse($date ?? $d['date_calculated'])->locale('te')->dayName;
            $label = ($label !== '' && $label !== $autoLabel) ? $label : null;
            $sehri = $request->input("sehri.{$n}");
            $sehri = ($sehri && $sehri !== $d['sehri_calculated']) ? $sehri : null;
            $iftar = $request->input("iftar.{$n}");
            $iftar = ($iftar && $iftar !== $d['iftar_calculated']) ? $iftar : null;

            $key = ['hijri_year' => $hijriYear, 'day' => $n];
            if ($date === null && $label === null && $sehri === null && $iftar === null) {
                $masjid->ramadanDayTimes()->where($key)->delete();
            } else {
                $masjid->ramadanDayTimes()->updateOrCreate($key, ['date' => $date, 'day_label' => $label, 'sehri' => $sehri, 'iftar' => $iftar]);
            }
        }

        return redirect()->to(route('admin.ramadan.edit', $hijriYear).'#timetable')
            ->with('status', "Ramadan {$hijriYear} timetable saved.");
    }

    /**
     * Go back to the calculated sehri/iftar times for every day.
     */
    public function resetDays(int $hijriYear): RedirectResponse
    {
        $this->masjid()->ramadanDayTimes()->where('hijri_year', $hijriYear)->delete();

        return redirect()->to(route('admin.ramadan.edit', $hijriYear).'#timetable')
            ->with('status', 'All days now use the calculated sehri and iftar times.');
    }

    public function storeDua(Request $request): RedirectResponse
    {
        $masjid = $this->masjid();
        $masjid->ramadanDuas()->create([
            ...$this->validateDua($request),
            'is_active' => $request->boolean('is_active'),
            'sort_order' => $request->filled('sort_order') ? $request->integer('sort_order') : $masjid->ramadanDuas()->max('sort_order') + 1,
        ]);

        return back()->with('status', 'Dua added.');
    }

    public function updateDua(Request $request, RamadanDua $dua): RedirectResponse
    {
        $this->ensureOwned($dua);
        $dua->update([
            ...$this->validateDua($request),
            'is_active' => $request->boolean('is_active'),
            'sort_order' => $request->filled('sort_order') ? $request->integer('sort_order') : $dua->sort_order,
        ]);

        return back()->with('status', 'Dua updated.');
    }

    public function destroyDua(RamadanDua $dua): RedirectResponse
    {
        $this->ensureOwned($dua);
        $dua->delete();

        return back()->with('status', 'Dua removed.');
    }

    /**
     * Upload (or replace) the masjid's printed Ramadan timetable.
     */
    public function storeImage(Request $request): RedirectResponse
    {
        $request->validate(['image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120']]);
        $masjid = $this->masjid();

        $old = $masjid->ramadan_image_path;
        $masjid->update(['ramadan_image_path' => $request->file('image')->store('ramadan-images')]);
        if ($old) {
            Storage::delete($old);
        }

        return back()->with('status', 'Timetable image saved.');
    }

    public function destroyImage(): RedirectResponse
    {
        $masjid = $this->masjid();
        if ($masjid->ramadan_image_path) {
            Storage::delete($masjid->ramadan_image_path);
            $masjid->update(['ramadan_image_path' => null]);
        }

        return back()->with('status', 'Timetable image removed.');
    }

    private function validateDua(Request $request): array
    {
        return $request->validate([
            'type' => ['required', Rule::in(array_keys(RamadanDua::TYPES))],
            'title' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:2000'],
            'sort_order' => ['nullable', 'integer', 'between:0,999'],
        ]);
    }
}
