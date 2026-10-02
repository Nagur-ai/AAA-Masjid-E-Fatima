@extends('layouts.public')

@section('title', $masjid->name.' · '.$month->format('F Y'))

@section('content')
    <div class="page-head">
        <div>
            <h1>{{ $month->format('F Y') }}</h1>
            <span class="muted">{{ $days[0]->hijri->monthName() }} – {{ end($days)->hijri->monthName() }} {{ end($days)->hijri->year }} AH &middot; {{ $masjid->timezone }}</span>
        </div>
        <div class="actions no-print">
            <a class="btn secondary" href="{{ route('masjid.timetable', [$masjid, 'month' => $month->subMonth()->format('Y-m')]) }}">&larr; {{ $month->subMonth()->format('M') }}</a>
            <a class="btn secondary" href="{{ route('masjid.timetable', [$masjid, 'month' => $month->addMonth()->format('Y-m')]) }}">{{ $month->addMonth()->format('M') }} &rarr;</a>
            <button class="btn secondary" onclick="window.print()">Print</button>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>Date</th><th>Hijri</th><th>Fajr</th><th>Sunrise</th><th>Dhuhr</th><th>Asr</th><th>Maghrib</th><th>Isha</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($days as $d)
                <tr @class(['highlight' => $d->date->toDateString() === $today, 'is-friday' => $d->date->isFriday()])>
                    <td>{{ $d->date->format('D j') }}</td>
                    <td>{{ $d->hijri->day }} {{ \Illuminate\Support\Str::limit($d->hijri->monthName(), 8, '') }}</td>
                    @foreach (['fajr', 'sunrise', 'dhuhr', 'asr', 'maghrib', 'isha'] as $p)
                        <td>
                            {{ $d->times[$p]?->format('H:i') ?? '—' }}
                            @isset($d->iqamah[$p])
                                <small>/ {{ $d->iqamah[$p]->format('H:i') }}</small>
                            @endisset
                        </td>
                    @endforeach
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <p class="muted" style="margin-top:8px">Adhan / <small>iqamah</small>. Fridays in bold.</p>
@endsection
