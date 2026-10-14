<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * Picks the website language: ?lang=te switches it (and remembers it for a year),
 * otherwise the visitor's saved choice, otherwise the default.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = array_keys(config('locales.supported'));
        $chosen = $request->query('lang');

        if (is_string($chosen) && in_array($chosen, $supported, true)) {
            $locale = $chosen;
            $request->session()->put('locale', $locale);
            Cookie::queue('locale', $locale, 60 * 24 * 365);
        } else {
            $saved = $request->session()->get('locale', $request->cookie('locale'));
            $locale = in_array($saved, $supported, true) ? $saved : config('locales.default');
        }

        App::setLocale($locale);
        Carbon::setLocale($locale);

        return $next($request);
    }
}
