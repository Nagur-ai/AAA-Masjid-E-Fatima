<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Web admin guard: the user must belong to a masjid as treasurer or admin
 * (or strictly admin with `staff:admin`). Shares the masjid with all views.
 */
class EnsureMasjidStaff
{
    public function handle(Request $request, Closure $next, ?string $requiredRole = null): Response
    {
        $user = $request->user();
        $masjid = $user?->masjid;

        // Members have no admin panel; send them to their own donations page.
        if ($masjid === null || ! $user->role->canManageFinance()) {
            return redirect()->route('account');
        }
        abort_if($requiredRole === Role::Admin->value && $user->role !== Role::Admin, 403, 'Only masjid administrators can do this.');

        $request->attributes->set('masjid', $masjid);
        View::share('currentMasjid', $masjid);

        return $next($request);
    }
}
