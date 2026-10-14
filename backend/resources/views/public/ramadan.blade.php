@extends('layouts.public')

@section('title', $masjid->name.' · '.__('Ramadan').' '.$calendar['hijri_year'])

@section('content')
    @include('partials.ramadan-image', ['url' => $calendar['timetable_image_url']])

    <div class="page-head">
        <div>
            <h1>{{ __('Ramadan :year AH', ['year' => $calendar['hijri_year']]) }}</h1>
            <span class="muted">
                {{ \Carbon\Carbon::parse($calendar['starts_on'])->translatedFormat('j M Y') }} – {{ \Carbon\Carbon::parse($calendar['ends_on'])->translatedFormat('j M Y') }}
                &middot; {{ __('Eid al-Fitr expected :date', ['date' => \Carbon\Carbon::parse($calendar['eid_al_fitr'])->translatedFormat('l j M')]) }}
            </span>
        </div>
        <div class="actions no-print">
            <a class="btn secondary" href="{{ route('masjid.ramadan', [$masjid, $calendar['hijri_year'] - 1]) }}">&larr; {{ $calendar['hijri_year'] - 1 }}</a>
            <a class="btn secondary" href="{{ route('masjid.ramadan', [$masjid, $calendar['hijri_year'] + 1]) }}">{{ $calendar['hijri_year'] + 1 }} &rarr;</a>
            <button class="btn secondary" onclick="window.print()">{{ __('Print') }}</button>
        </div>
    </div>

    @unless ($calendar['is_confirmed'])
        <div class="alert warn">{{ __('These dates are an estimate from the Umm al-Qura calendar. The masjid will confirm the start of Ramadan after the moon sighting.') }}</div>
    @endunless
    @if ($calendar['notes'] || $calendar['taraweeh_time'])
        <div class="alert success">
            @if ($calendar['taraweeh_time']) <strong>{{ __('Taraweeh') }}</strong> {{ __('at :time', ['time' => $calendar['taraweeh_time']]) }}. @endif
            {{ $calendar['notes'] }}
        </div>
    @endif

    <div class="card" style="margin-bottom:16px">
        <h2 lang="te">సహరీ & ఇఫ్తార్ దుఆలు</h2>
        @include('partials.ramadan-duas', ['duas' => $calendar['duas']])
    </div>

    @include('partials.ramadan-table', ['days' => $calendar['days'], 'todayStr' => $today, 'title' => $masjid->name])
    <p class="muted" style="margin-top:8px">{{ __('Sehri ends at Fajr; stop eating by imsak to be safe.') }}</p>

@endsection
