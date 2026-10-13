<?php

namespace App\Http\Controllers\Web\Admin;

use App\Enums\DonationStatus;
use App\Enums\Role;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Services\Finance\ReportService;
use App\Services\PrayerTimes\PrayerScheduleService;
use App\Services\Ramadan\RamadanCalendarService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * One dashboard, assembled per role: only the panels the role may see are
 * queried and rendered (see Role::dashboardPanels()).
 */
class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        ReportService $reports,
        PrayerScheduleService $schedules,
        RamadanCalendarService $ramadan,
    ): View {
        $masjid = $this->masjid();
        $today = CarbonImmutable::now($masjid->timezone);
        $panels = $request->user()->role->dashboardPanels();
        $shows = fn (string ...$names) => array_intersect($names, $panels) !== [];

        $data = [
            'panels' => $panels,
            'today' => $today,
            'hijri' => $schedules->forDate($masjid, $today)->hijri,
        ];

        if ($shows(Role::PANEL_SUMMARY, Role::PANEL_FUNDS)) {
            $data['summary'] = $reports->summary($masjid, $today->startOfMonth()->toDateString(), $today->toDateString());
        }

        if ($shows(Role::PANEL_PENDING)) {
            $data['pendingDonations'] = $masjid->donations()->with('fund')
                ->where('status', DonationStatus::Pending)->oldest('id')->limit(5)->get();
            $data['pendingCount'] = $masjid->donations()->where('status', DonationStatus::Pending)->count();
        }

        if ($shows(Role::PANEL_DONATIONS)) {
            $data['recentDonations'] = $masjid->donations()->with('fund')
                ->where('status', '!=', DonationStatus::Pending)->latest('id')->limit(6)->get();
        }

        if ($shows(Role::PANEL_EXPENSES)) {
            $data['recentExpenses'] = $masjid->transactions()->with('category', 'fund')
                ->where('type', TransactionType::Expense)->posted()
                ->orderByDesc('transacted_on')->orderByDesc('id')->limit(6)->get();
        }

        if ($shows(Role::PANEL_PRAYERS)) {
            $data['schedule'] = $schedules->forDate($masjid, $today);
        }

        if ($shows(Role::PANEL_RAMADAN)) {
            $year = $ramadan->upcomingYear($masjid, $today);
            $data['ramadan'] = $ramadan->build($masjid, $year);
        }

        if ($shows(Role::PANEL_STAFF)) {
            $data['staff'] = $masjid->users()->whereIn('role', [Role::Admin, Role::Treasurer])->orderBy('role')->orderBy('name')->get();
        }

        return view('admin.dashboard', $data);
    }
}
