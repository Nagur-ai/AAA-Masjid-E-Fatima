<?php

namespace App\Http\Controllers\Web\Admin;

use App\Enums\Prayer;
use App\Http\Controllers\Controller;
use App\Services\PrayerTimes\DailySchedule;
use App\Services\PrayerTimes\PrayerScheduleService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The admin changes the adhan and iqamah times of one day (today by default).
 */
class PrayerTimeController extends Controller
{
    public function __construct(private readonly PrayerScheduleService $schedules) {}

    public function edit(Request $request): View
    {
        $masjid = $this->masjid();
        $date = $this->date($request);

        return view('admin.prayer-times.edit', [
            'date' => $date,
            'today' => CarbonImmutable::now($masjid->timezone)->startOfDay(),
            'schedule' => $this->schedules->forDate($masjid, $date),
            'calculated' => $this->schedules->forDate($masjid, $date, withOverrides: false),
            'edited' => $masjid->prayerTimeOverrides()->whereDate('date', $date->toDateString())->get()->keyBy(fn ($o) => $o->prayer->value),
            'prayers' => Prayer::cases(),
        ]);
    }

    /**
     * Store only the times that differ from the calculation (adhan) or the iqamah rule.
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'adhan' => ['array'], 'adhan.*' => ['nullable', 'date_format:H:i'],
            'iqamah' => ['array'], 'iqamah.*' => ['nullable', 'date_format:H:i'],
        ]);
        $masjid = $this->masjid();
        $date = $this->date($request);
        $calculated = $this->schedules->forDate($masjid, $date, withOverrides: false);
        $current = $this->schedules->forDate($masjid, $date);
        $existing = $masjid->prayerTimeOverrides()->whereDate('date', $date->toDateString())->get()->keyBy(fn ($o) => $o->prayer->value);

        if ($errors = $this->orderErrors($request, $calculated, $current, $existing)) {
            return back()->withErrors($errors)->withInput();
        }

        foreach (Prayer::cases() as $prayer) {
            $p = $prayer->value;
            $adhan = $request->input("adhan.{$p}");
            $adhan = ($adhan && $adhan !== $calculated->times[$p]?->format('H:i')) ? $adhan : null;
            $iqamah = $request->input("iqamah.{$p}");
            // An untouched rule-based iqamah keeps following the rule (and so a changed adhan).
            $untouchedRule = ! $existing->get($p)?->iqamah && $iqamah === $current->iqamah[$p]?->format('H:i');
            $iqamah = ($iqamah && ! $untouchedRule && $iqamah !== $calculated->iqamah[$p]?->format('H:i')) ? $iqamah : null;

            $key = ['date' => $date->toDateString(), 'prayer' => $p];
            if ($adhan === null && $iqamah === null) {
                $masjid->prayerTimeOverrides()->whereDate('date', $key['date'])->where('prayer', $p)->delete();
            } else {
                $masjid->prayerTimeOverrides()->updateOrCreate($key, ['adhan' => $adhan, 'iqamah' => $iqamah]);
            }
        }

        return redirect()->route('admin.prayer-times.edit', ['date' => $date->toDateString()])
            ->with('status', 'Prayer times for '.$date->format('l j F Y').' saved.');
    }

    public function reset(Request $request): RedirectResponse
    {
        $request->validate(['date' => ['required', 'date_format:Y-m-d']]);
        $date = $this->date($request);
        $this->masjid()->prayerTimeOverrides()->whereDate('date', $date->toDateString())->delete();

        return redirect()->route('admin.prayer-times.edit', ['date' => $date->toDateString()])
            ->with('status', 'Prayer times for '.$date->format('l j F Y').' are calculated again.');
    }

    /**
     * Catch typing slips such as Dhuhr at 01:00 instead of 13:00: adhan times must follow the
     * order of the day, and an iqamah cannot come before its adhan. An untouched rule-based
     * iqamah is skipped: it moves with the adhan when saved.
     *
     * @param  \Illuminate\Support\Collection<string, \App\Models\PrayerTimeOverride>  $existing
     * @return array<string, string>
     */
    private function orderErrors(Request $request, DailySchedule $calculated, DailySchedule $current, $existing): array
    {
        $errors = [];
        $previous = null;

        foreach (Prayer::cases() as $prayer) {
            $p = $prayer->value;
            $adhan = $request->input("adhan.{$p}") ?: $calculated->times[$p]?->format('H:i');
            $iqamah = $request->input("iqamah.{$p}");
            if (! $existing->get($p)?->iqamah && $iqamah === $current->iqamah[$p]?->format('H:i')) {
                $iqamah = null;
            }

            if ($adhan && $previous && $adhan <= $previous[1]) {
                $hint = (int) substr($adhan, 0, 2) < 12 ? ' Did you mean '.sprintf('%02d', (int) substr($adhan, 0, 2) + 12).substr($adhan, 2).'?' : '';
                $errors["adhan.{$p}"] = "{$prayer->label()} adhan ({$adhan}) must be after {$previous[0]} ({$previous[1]}).{$hint}";
            }
            if ($adhan && $iqamah && $iqamah < $adhan) {
                $errors["iqamah.{$p}"] = "{$prayer->label()} iqamah ({$iqamah}) cannot be before its adhan ({$adhan}).";
            }
            if ($adhan) {
                $previous = [$prayer->label(), $adhan];
            }
        }

        return $errors;
    }

    private function date(Request $request): CarbonImmutable
    {
        $tz = $this->masjid()->timezone;
        $value = (string) $request->input('date');

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)
            ? CarbonImmutable::createFromFormat('!Y-m-d', $value, $tz)
            : CarbonImmutable::now($tz)->startOfDay();
    }
}
