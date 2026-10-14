<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\CommitteeMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The masjid admin keeps the committee list (shown on the public site) up to date.
 */
class CommitteeController extends Controller
{
    public function index(): View
    {
        return view('admin.committee.index', [
            'members' => $this->masjid()->committeeMembers()->get()->sortBy(fn ($m) => [$m->roleRank(), $m->sort_order, $m->id])->values(),
            'roles' => CommitteeMember::ROLES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $masjid = $this->masjid();
        $member = $masjid->committeeMembers()->create([
            ...$this->validated($request),
            'show_phone' => $request->boolean('show_phone'),
            'is_active' => $request->boolean('is_active'),
            'sort_order' => $request->filled('sort_order') ? $request->integer('sort_order') : $masjid->committeeMembers()->max('sort_order') + 1,
        ]);
        $this->savePhoto($request, $member);

        return back()->with('status', "{$member->name} added to the committee.");
    }

    public function update(Request $request, CommitteeMember $member): RedirectResponse
    {
        $this->ensureOwned($member);
        $member->update([
            ...$this->validated($request),
            'show_phone' => $request->boolean('show_phone'),
            'is_active' => $request->boolean('is_active'),
            'sort_order' => $request->filled('sort_order') ? $request->integer('sort_order') : $member->sort_order,
        ]);
        if ($request->boolean('remove_photo')) {
            $this->deletePhoto($member);
        }
        $this->savePhoto($request, $member);

        return back()->with('status', "{$member->name} updated.");
    }

    public function destroy(CommitteeMember $member): RedirectResponse
    {
        $this->ensureOwned($member);
        $this->deletePhoto($member);
        $member->delete();

        return back()->with('status', "{$member->name} removed from the committee.");
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'role' => ['required', Rule::in(array_keys(CommitteeMember::ROLES))],
            'title' => ['nullable', 'string', 'max:80', 'required_if:role,other'],
            'phone' => ['nullable', 'string', 'max:32'],
            'sort_order' => ['nullable', 'integer', 'between:0,999'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);
        unset($data['photo'], $data['sort_order']);

        return $data;
    }

    private function savePhoto(Request $request, CommitteeMember $member): void
    {
        if (! $request->hasFile('photo')) {
            return;
        }
        $this->deletePhoto($member);
        $member->forceFill(['photo_path' => $request->file('photo')->store('committee-photos')])->save();
    }

    private function deletePhoto(CommitteeMember $member): void
    {
        if ($member->photo_path) {
            Storage::delete($member->photo_path);
            $member->forceFill(['photo_path' => null])->save();
        }
    }
}
