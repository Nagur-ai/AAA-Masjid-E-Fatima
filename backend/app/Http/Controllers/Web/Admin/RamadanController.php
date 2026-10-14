<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateRamadanRequest;
use App\Models\RamadanDua;
use App\Services\Ramadan\RamadanCalendarService;
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
