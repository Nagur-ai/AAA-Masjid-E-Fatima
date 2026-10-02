<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\PledgeDonationRequest;
use App\Models\Masjid;
use App\Services\Finance\DonationService;
use App\Services\PrayerTimes\PrayerScheduleService;
use App\Services\Ramadan\RamadanCalendarService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicController extends Controller
{
    public function home(): View|RedirectResponse
    {
        $masjids = Masjid::orderBy('name')->get(['id', 'slug', 'name', 'city']);

        return $masjids->count() === 1
            ? redirect()->route('masjid.show', $masjids->first())
            : view('public.home', ['masjids' => $masjids]);
    }

    public function show(Masjid $masjid, PrayerScheduleService $schedules): View
    {
        $now = CarbonImmutable::now($masjid->timezone);
        $today = $schedules->forDate($masjid, $now);
        $next = $today->nextPrayer($now) ?? $schedules->forDate($masjid, $now->addDay())->nextPrayer($now);

        return view('public.masjid', [
            'masjid' => $masjid->load('jumuahSessions'),
            'today' => $today,
            'next' => $next,
            'now' => $now,
        ]);
    }

    public function timetable(Request $request, Masjid $masjid, PrayerScheduleService $schedules): View
    {
        $month = CarbonImmutable::now($masjid->timezone)->startOfMonth();
        if (preg_match('/^\d{4}-\d{2}$/', (string) $request->query('month'))) {
            $month = CarbonImmutable::createFromFormat('!Y-m', $request->query('month'), $masjid->timezone);
        }

        return view('public.timetable', [
            'masjid' => $masjid,
            'month' => $month,
            'days' => $schedules->forMonth($masjid, $month->year, $month->month),
            'today' => CarbonImmutable::now($masjid->timezone)->toDateString(),
        ]);
    }

    public function ramadan(Masjid $masjid, RamadanCalendarService $ramadan, ?int $hijriYear = null): View
    {
        $hijriYear ??= $ramadan->upcomingYear($masjid);
        abort_unless($hijriYear >= 1400 && $hijriYear <= 1600, 404);

        return view('public.ramadan', [
            'masjid' => $masjid,
            'calendar' => $ramadan->build($masjid, $hijriYear),
            'today' => CarbonImmutable::now($masjid->timezone)->toDateString(),
        ]);
    }

    public function donateForm(Masjid $masjid): View
    {
        return view('public.donate', [
            'masjid' => $masjid,
            'funds' => $masjid->funds()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function donate(PledgeDonationRequest $request, Masjid $masjid, DonationService $donations): RedirectResponse
    {
        $donation = $donations->pledge($masjid, $request->validated(), $request->user());

        return redirect()->route('masjid.donate', $masjid)->with('pledge', [
            'id' => $donation->id,
            'amount' => $donation->amount,
            'currency' => $donation->currency,
            'fund' => $donation->fund->name,
        ]);
    }
}
