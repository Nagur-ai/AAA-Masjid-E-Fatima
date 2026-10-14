@extends('layouts.public')

@section('title', $masjid->name.' · '.__('Prayer times'))

@section('content')
    <section @class(['hero', 'hero-photo' => $masjid->banner_image_path])
        @if ($masjid->banner_image_path) style="background-image: linear-gradient(180deg, rgba(8, 48, 36, .30), rgba(8, 48, 36, .85)), url('{{ route('masjid.banner', $masjid) }}'); background-position: center {{ $masjid->banner_position }};" @endif>
        <div class="grid grid-2" style="align-items:end">
            <div>
                <h1>{{ $masjid->name }}</h1>
                <div class="muted">{{ $today->date->translatedFormat('l, j F Y') }} &middot; {{ $today->hijri->format() }}</div>
            </div>
            @if ($next)
                <div class="right">
                    <div class="muted">{{ __('Next: :prayer', ['prayer' => __(ucfirst($next['prayer']))]) }}</div>
                    <div class="next-prayer">{{ $next['at']->format('H:i') }}</div>
                    <div class="muted">{{ __('in :time', ['time' => $now->diff($next['at'])->format('%hh %Im')]) }}</div>
                </div>
            @endif
        </div>
    </section>

    <div class="prayers" style="margin-bottom:16px">
        @foreach (['fajr' => 'Fajr', 'sunrise' => 'Sunrise', 'dhuhr' => 'Dhuhr', 'asr' => 'Asr', 'maghrib' => 'Maghrib', 'isha' => 'Isha'] as $key => $label)
            @php($isNext = $next && $next['prayer'] === $key)
            <div @class(['prayer', 'is-next' => $isNext])>
                @if ($isNext)<span class="next-tag">{{ $next['at']->isSameDay($today->date) ? __('Next') : __('Next · tomorrow') }}</span>@endif
                <div class="name">{{ __($key === 'dhuhr' && $today->date->isFriday() ? 'Jumuah' : $label) }}</div>
                <div class="adhan">{{ $today->times[$key]?->format('H:i') ?? '—' }}</div>
                <div class="iqamah">
                    @if ($key === 'sunrise')
                        {{ __('Ishraq after :time', ['time' => $today->times['sunrise']?->addMinutes(15)->format('H:i')]) }}
                    @elseif (isset($today->iqamah[$key]))
                        {{ __('Iqamah :time', ['time' => $today->iqamah[$key]->format('H:i')]) }}
                    @else
                        &nbsp;
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid grid-2" style="margin-bottom:16px">
        <div class="card sehri-iftar">
            <div class="page-head" style="margin-bottom:10px">
                <h2 style="margin:0">{{ __('Sehri & Iftar') }}</h2>
                @if ($ramadanToday)
                    <span class="badge confirmed">{{ __('Roza :n', ['n' => $ramadanToday['day']]) }}</span>
                @endif
            </div>
            <div class="si-date">
                {{ $today->date->translatedFormat('l, j F Y') }}
                @if (app()->getLocale() === 'en') &middot; <span lang="te">{{ $today->date->locale('te')->dayName }}</span>@endif
                <br><small class="muted">{{ $today->hijri->format() }}</small>
            </div>
            <div class="si-times">
                <div class="si-box si-sehri">
                    <div class="si-label">{{ __('Sehri ends') }}</div>
                    <div class="si-time">{{ $ramadanToday['sehri'] ?? $today->times['fajr']?->format('H:i') ?? '—' }}</div>
                    <small class="muted">{{ __('Imsak :time', ['time' => $ramadanToday['imsak'] ?? $today->times['imsak']?->format('H:i') ?? '—']) }}</small>
                </div>
                <div class="si-box si-iftar">
                    <div class="si-label">{{ __('Iftar') }}</div>
                    <div class="si-time">{{ $ramadanToday['iftar'] ?? $today->times['maghrib']?->format('H:i') ?? '—' }}</div>
                    <small class="muted">{{ __('At sunset (Maghrib)') }}</small>
                </div>
            </div>
        </div>

        <div class="card">
            <h2>{{ __('Jumuah') }}</h2>
            @if ($masjid->jumuahSessions->isEmpty())
                <p class="muted">{{ __('Jumuah times have not been announced.') }}</p>
            @else
                <div class="table-wrap jumuah-table">
                    <table>
                        <thead><tr><th>{{ __('Session') }}</th><th>{{ __('Khutbah') }}</th><th>{{ __('Iqamah') }}</th><th>{{ __('Language') }}</th></tr></thead>
                        <tbody>
                        @foreach ($masjid->jumuahSessions as $s)
                            <tr>
                                <td><strong>{{ $s->label }}</strong></td>
                                <td class="time">{{ substr($s->khutbah_time, 0, 5) }}</td>
                                <td class="time">{{ substr($s->iqamah_time, 0, 5) }}</td>
                                <td>{{ $s->language ?: '—' }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($today->date->isFriday())
                    <p class="muted" style="margin:8px 0 0">{{ __('Today is Friday: Jumuah replaces Dhuhr.') }}</p>
                @endif
            @endif
        </div>
    </div>

    <div class="grid grid-2">
        <div class="card">
            <h2>{{ __('Today') }}</h2>
            <p>{{ __('Imsak :imsak · Sunset :sunset · Midnight :midnight', [
                'imsak' => $today->times['imsak']?->format('H:i'),
                'sunset' => $today->times['sunset']?->format('H:i'),
                'midnight' => $today->times['midnight']?->format('H:i'),
            ]) }}</p>
            <div class="actions">
                <a class="btn" href="{{ route('masjid.donate', $masjid) }}">{{ __('Donate') }}</a>
                <a class="btn secondary" href="{{ route('masjid.timetable', $masjid) }}">{{ __('Monthly timetable') }}</a>
                <a class="btn secondary" href="{{ route('masjid.ramadan', $masjid) }}">{{ __('Ramadan calendar') }}</a>
                <a class="btn secondary" href="{{ route('masjid.committee', $masjid) }}">{{ __('Masjid committee') }}</a>
            </div>
        </div>
    </div>
@endsection
