<?php

namespace App\Http\Controllers\Web\Admin;

use App\Enums\AsrMethod;
use App\Enums\CalculationMethod;
use App\Enums\HighLatitudeRule;
use App\Enums\Prayer;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreIqamahRuleRequest;
use App\Http\Requests\UpdateMasjidRequest;
use App\Models\IqamahRule;
use App\Models\JumuahSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(): View
    {
        $masjid = $this->masjid();

        return view('admin.settings.edit', [
            'masjid' => $masjid,
            'rules' => $masjid->iqamahRules()->orderByDesc('effective_from')->get()->groupBy(fn ($r) => $r->prayer->value),
            'jumuah' => $masjid->jumuahSessions,
            'methods' => CalculationMethod::cases(),
            'asrMethods' => AsrMethod::cases(),
            'highLatitudeRules' => HighLatitudeRule::cases(),
            'prayers' => Prayer::cases(),
            'timezones' => \DateTimeZone::listIdentifiers(),
        ]);
    }

    public function update(UpdateMasjidRequest $request): RedirectResponse
    {
        $this->masjid()->update($request->validated());

        return back()->with('status', 'Masjid settings saved. Prayer times are recalculated automatically.');
    }

    public function storeIqamah(StoreIqamahRuleRequest $request): RedirectResponse
    {
        $this->masjid()->iqamahRules()->create($request->validated());

        return back()->with('status', 'Iqamah rule added.');
    }

    public function updateIqamah(StoreIqamahRuleRequest $request, IqamahRule $rule): RedirectResponse
    {
        $this->ensureOwned($rule);
        $rule->update($request->validated());

        return back()->with('status', 'Iqamah rule updated.');
    }

    public function destroyIqamah(IqamahRule $rule): RedirectResponse
    {
        $this->ensureOwned($rule);
        $rule->delete();

        return back()->with('status', 'Iqamah rule removed.');
    }

    public function storeJumuah(Request $request): RedirectResponse
    {
        $data = $this->validateJumuah($request);
        $masjid = $this->masjid();
        $masjid->jumuahSessions()->create([...$data, 'sort_order' => $masjid->jumuahSessions()->count()]);

        return back()->with('status', 'Jumuah session added.');
    }

    public function updateJumuah(Request $request, JumuahSession $session): RedirectResponse
    {
        $this->ensureOwned($session);
        $session->update($this->validateJumuah($request));

        return back()->with('status', 'Jumuah session updated.');
    }

    public function destroyJumuah(JumuahSession $session): RedirectResponse
    {
        $this->ensureOwned($session);
        $session->delete();

        return back()->with('status', 'Jumuah session removed.');
    }

    private function validateJumuah(Request $request): array
    {
        return $request->validate([
            'label' => ['required', 'string', 'max:60'],
            'khutbah_time' => ['required', 'date_format:H:i'],
            'iqamah_time' => ['required', 'date_format:H:i', 'after:khutbah_time'],
            'language' => ['nullable', 'string', 'max:32'],
        ]);
    }

    /**
     * Upload (or replace) the photo behind the masjid name on the public home page,
     * or just change which part of it is shown.
     */
    public function storeBanner(Request $request): RedirectResponse
    {
        $request->validate([
            'banner' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'banner_position' => ['required', Rule::in(['top', 'center', 'bottom'])],
        ]);
        $masjid = $this->masjid();
        $masjid->banner_position = $request->input('banner_position');

        if ($request->hasFile('banner')) {
            $old = $masjid->banner_image_path;
            $masjid->banner_image_path = $request->file('banner')->store('banners');
            if ($old) {
                Storage::delete($old);
            }
        }
        $masjid->save();

        return back()->with('status', 'Banner photo saved.');
    }

    public function destroyBanner(): RedirectResponse
    {
        $masjid = $this->masjid();
        if ($masjid->banner_image_path) {
            Storage::delete($masjid->banner_image_path);
            $masjid->banner_image_path = null;
            $masjid->save();
        }

        return back()->with('status', 'Banner photo removed.');
    }
}
