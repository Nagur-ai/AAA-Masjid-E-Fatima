<?php

namespace App\Providers;

use App\Models\Donation;
use App\Models\Masjid;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Catch N+1 queries and silently dropped attributes outside production.
        Model::preventLazyLoading(! $this->app->isProduction());
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());
        Paginator::useBootstrapFour();

        // Store short, stable names in polymorphic columns instead of class names.
        Relation::enforceMorphMap(['donation' => Donation::class, 'user' => User::class]);

        Gate::define('manage-masjid', fn (User $user, Masjid $masjid) => $user->isAdminOf($masjid));
        Gate::define('manage-finance', fn (User $user, Masjid $masjid) => $user->canManageFinanceOf($masjid));

        RateLimiter::for('auth', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));
        RateLimiter::for('donations', fn (Request $request) => Limit::perMinute(20)->by($request->user()?->id ?: $request->ip()));
    }
}
