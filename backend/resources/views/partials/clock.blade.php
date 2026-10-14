{{-- Live clock for the right side of the top bar, in the masjid's timezone. --}}
@php
    // Pages without a masjid (sign-in, My donations) use the user's masjid, else the first one.
    $clockMasjid ??= auth()->user()?->masjid ?? \App\Models\Masjid::orderBy('id')->first();
    $tz = $clockMasjid?->timezone ?? config('app.timezone');
    $now = now($tz);
    $hijri = $clockMasjid
        ? app(\App\Services\Hijri\HijriCalendar::class)->fromGregorian($now, $clockMasjid->hijri_adjustment)->format()
        : null;
@endphp
<div class="clock" data-tz="{{ $tz }}" title="Local time at {{ $clockMasjid?->name ?? config('app.name') }} ({{ $tz }})">
    <time class="clock-time" datetime="{{ $now->toIso8601String() }}">{{ $now->format('H:i:s') }}</time>
    <span class="clock-date">{{ $now->translatedFormat('D j M') }}@if($hijri) &middot; {{ $hijri }}@endif</span>
</div>
<script>
    (function () {
        var el = document.currentScript.previousElementSibling;
        var timeEl = el.querySelector('.clock-time');
        var fmt;
        try {
            fmt = new Intl.DateTimeFormat('en-GB', { timeZone: el.dataset.tz, hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false });
        } catch (e) {
            return; // Unknown timezone in this browser: keep the server-rendered time.
        }
        function tick() { timeEl.textContent = fmt.format(new Date()); }
        tick();
        setInterval(tick, 1000);
    })();
</script>
