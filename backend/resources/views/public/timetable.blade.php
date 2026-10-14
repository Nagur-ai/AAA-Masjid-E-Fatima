@extends('layouts.public')

@section('title', $masjid->name.' · '.$month->translatedFormat('F Y'))

@section('content')
    <div class="page-head">
        <div>
            <h1>{{ $month->translatedFormat('F Y') }}</h1>
            <span class="muted">{{ __($days[0]->hijri->monthName()) }} – {{ __(end($days)->hijri->monthName()) }} {{ end($days)->hijri->year }} {{ __('AH') }} &middot; {{ $masjid->timezone }}</span>
        </div>
        <div class="actions no-print">
            <a class="btn secondary" href="{{ route('masjid.timetable', [$masjid, 'month' => $month->subMonth()->format('Y-m')]) }}">&larr; {{ $month->subMonth()->translatedFormat('M') }}</a>
            <a class="btn secondary" href="{{ route('masjid.timetable', [$masjid, 'month' => $month->addMonth()->format('Y-m')]) }}">{{ $month->addMonth()->translatedFormat('M') }} &rarr;</a>
            <button class="btn secondary" onclick="window.print()">{{ __('Print') }}</button>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>{{ __('Date') }}</th><th>{{ __('Hijri') }}</th><th>{{ __('Fajr') }}</th><th>{{ __('Sunrise') }}</th><th>{{ __('Dhuhr') }}</th><th>{{ __('Asr') }}</th><th>{{ __('Maghrib') }}</th><th>{{ __('Isha') }}</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($days as $d)
                <tr @class(['highlight' => $d->date->toDateString() === $today, 'is-friday' => $d->date->isFriday()])>
                    <td>{{ $d->date->translatedFormat('D j') }}</td>
                    <td>{{ $d->hijri->day }} {{ \Illuminate\Support\Str::limit(__($d->hijri->monthName()), 10, '') }}</td>
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
    <p class="muted" style="margin-top:8px">{{ __('Adhan') }} / <small>{{ __('Iqamah') }}</small>. {{ __('Fridays in bold.') }}</p>
@endsection
