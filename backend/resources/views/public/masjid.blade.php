@extends('layouts.public')

@section('title', $masjid->name.' · Prayer times')

@section('content')
    <section class="hero">
        <div class="grid grid-2" style="align-items:end">
            <div>
                <h1>{{ $masjid->name }}</h1>
                <div class="muted">{{ $today->date->format('l, j F Y') }} &middot; {{ $today->hijri->format() }}</div>
            </div>
            @if ($next)
                <div class="right">
                    <div class="muted">Next: {{ ucfirst($next['prayer']) }}</div>
                    <div class="next-prayer">{{ $next['at']->format('H:i') }}</div>
                    <div class="muted">in {{ $now->diff($next['at'])->format('%hh %Im') }}</div>
                </div>
            @endif
        </div>
    </section>

    <div class="prayers" style="margin-bottom:16px">
        @foreach (['fajr' => 'Fajr', 'sunrise' => 'Sunrise', 'dhuhr' => 'Dhuhr', 'asr' => 'Asr', 'maghrib' => 'Maghrib', 'isha' => 'Isha'] as $key => $label)
            @php($isNext = $next && $next['prayer'] === $key)
            <div @class(['prayer', 'is-next' => $isNext])>
                @if ($isNext)<span class="next-tag">{{ $next['at']->isSameDay($today->date) ? 'Next' : 'Next · tomorrow' }}</span>@endif
                <div class="name">{{ $key === 'dhuhr' && $today->date->isFriday() ? 'Jumuah' : $label }}</div>
                <div class="adhan">{{ $today->times[$key]?->format('H:i') ?? '—' }}</div>
                <div class="iqamah">
                    @if ($key === 'sunrise')
                        Ishraq after {{ $today->times['sunrise']?->addMinutes(15)->format('H:i') }}
                    @elseif (isset($today->iqamah[$key]))
                        Iqamah {{ $today->iqamah[$key]->format('H:i') }}
                    @else
                        &nbsp;
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid grid-2">
        <div class="card">
            <h2>Jumuah</h2>
            @forelse ($masjid->jumuahSessions as $s)
                <p><strong>{{ $s->label }}</strong>{{ $s->language ? " ({$s->language})" : '' }}:
                    khutbah {{ substr($s->khutbah_time, 0, 5) }}, iqamah {{ substr($s->iqamah_time, 0, 5) }}</p>
            @empty
                <p class="muted">Jumuah times have not been announced.</p>
            @endforelse
        </div>
        <div class="card">
            <h2>Today</h2>
            <p>Imsak {{ $today->times['imsak']?->format('H:i') }} &middot; Sunset {{ $today->times['sunset']?->format('H:i') }}
                &middot; Midnight {{ $today->times['midnight']?->format('H:i') }}</p>
            <div class="actions">
                <a class="btn" href="{{ route('masjid.donate', $masjid) }}">Donate</a>
                <a class="btn secondary" href="{{ route('masjid.timetable', $masjid) }}">Monthly timetable</a>
                <a class="btn secondary" href="{{ route('masjid.ramadan', $masjid) }}">Ramadan calendar</a>
            </div>
        </div>
    </div>
@endsection
