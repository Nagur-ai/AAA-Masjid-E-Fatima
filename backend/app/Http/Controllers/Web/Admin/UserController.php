<?php

namespace App\Http\Controllers\Web\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Masjid admins appoint other admins and treasurers.
 */
class UserController extends Controller
{
    public function index(): View
    {
        return view('admin.users.index', [
            'users' => $this->masjid()->users()->orderBy('name')->get(),
            'roles' => [Role::Admin, Role::Treasurer],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:32'],
            'role' => ['required', Rule::enum(Role::class)->only([Role::Admin, Role::Treasurer])],
            'password' => ['required', Password::defaults()],
        ]);

        $user = new User($request->only('name', 'email', 'phone', 'password'));
        $user->role = Role::from($data['role']);
        $user->masjid()->associate($this->masjid());
        $user->save();

        return back()->with('status', "{$user->name} added as {$user->role->value}.");
    }

    /**
     * Change a staff member's role (set them back to a regular member to revoke access),
     * and optionally their name, email, phone or password.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $this->ensureOwned($user);
        $data = $request->validate([
            'role' => ['required', Rule::enum(Role::class)],
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['sometimes', 'nullable', 'string', 'max:32'],
            'password' => ['nullable', Password::defaults()],
        ]);

        if ($user->is($request->user()) && $data['role'] !== Role::Admin->value) {
            return back()->withErrors(['role' => 'You cannot remove your own admin access.']);
        }

        $user->fill(array_intersect_key($data, array_flip(['name', 'email', 'phone'])));
        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }
        $user->role = Role::from($data['role']);
        $user->save();

        return back()->with('status', "{$user->name} updated.");
    }

    /**
     * Remove a person from this masjid. Anyone who recorded ledger entries is kept for the
     * audit trail; set them to "member" instead.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->ensureOwned($user);
        if ($user->is($request->user())) {
            return back()->withErrors(['user' => 'You cannot delete your own account.']);
        }
        if (Transaction::where('recorded_by', $user->id)->orWhere('voided_by', $user->id)->exists()) {
            return back()->withErrors(['user' => "{$user->name} recorded ledger entries, so the account is kept for the audit trail. Set the role to member to remove their access."]);
        }
        $user->delete();

        return back()->with('status', "{$user->name} deleted.");
    }
}
