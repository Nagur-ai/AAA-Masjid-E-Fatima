@extends('layouts.public')

@section('title', $masjid->name.' · Ramadan '.$calendar['hijri_year'])

@section('content')
    <div class="page-head">
        <div>
            <h1>Ramadan {{ $calendar['hijri_year'] }} AH</h1>
            <span class="muted">
                {{ \Carbon\Carbon::parse($calendar['starts_on'])->format('j M Y') }} – {{ \Carbon\Carbon::parse($calendar['ends_on'])->format('j M Y') }}
                &middot; Eid al-Fitr expected {{ \Carbon\Carbon::parse($calendar['eid_al_fitr'])->format('l j M') }}
            </span>
        </div>
        <div class="actions no-print">
            <a class="btn secondary" href="{{ route('masjid.ramadan', [$masjid, $calendar['hijri_year'] - 1]) }}">&larr; {{ $calendar['hijri_year'] - 1 }}</a>
            <a class="btn secondary" href="{{ route('masjid.ramadan', [$masjid, $calendar['hijri_year'] + 1]) }}">{{ $calendar['hijri_year'] + 1 }} &rarr;</a>
            <button class="btn secondary" onclick="window.print()">Print</button>
        </div>
    </div>

    @unless ($calendar['is_confirmed'])
        <div class="alert warn">These dates are an estimate from the Umm al-Qura calendar. The masjid will confirm the start of Ramadan after the moon sighting.</div>
    @endunless
    @if ($calendar['notes'] || $calendar['taraweeh_time'])
        <div class="alert success">
            @if ($calendar['taraweeh_time']) <strong>Taraweeh</strong> at {{ $calendar['taraweeh_time'] }}. @endif
            {{ $calendar['notes'] }}
        </div>
    @endif

    <div class="table-wrap">
        <table>
            <thead>
            <tr><th>Day</th><th>Date</th><th>Sehri ends</th><th>Fajr</th><th>Dhuhr</th><th>Asr</th><th>Iftar</th><th>Isha</th><th></th></tr>
            </thead>
            <tbody>
            @foreach ($calendar['days'] as $d)
                <tr @class(['highlight' => $d['date'] === $today])>
                    <td><strong>{{ $d['day'] }}</strong></td>
                    <td>{{ \Carbon\Carbon::parse($d['date'])->format('D j M') }}</td>
                    <td><strong>{{ $d['sehri_ends'] }}</strong> <small>imsak {{ $d['imsak'] }}</small></td>
                    <td>{{ $d['times']['fajr'] }}</td>
                    <td>{{ $d['times']['dhuhr'] }}</td>
                    <td>{{ $d['times']['asr'] }}</td>
                    <td><strong>{{ $d['iftar'] }}</strong></td>
                    <td>{{ $d['times']['isha'] }}</td>
                    <td>
                        @if ($d['odd_night_after_iftar'])
                            <span class="badge pending">Night {{ $d['odd_night_after_iftar'] }}</span>
                        @elseif ($d['is_last_ten'])
                            <span class="badge">Last 10</span>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <p class="muted" style="margin-top:8px">Sehri ends at Fajr; stop eating by imsak to be safe. Odd nights of the last ten, when Laylat al-Qadr is sought, begin after iftar on the marked day.</p>
@endsection
