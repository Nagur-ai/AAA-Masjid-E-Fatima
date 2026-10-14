@extends('layouts.admin')

@section('title', 'Prayer times')

@php($names_te = ['fajr' => 'ఫజర్', 'dhuhr' => 'జుహర్', 'asr' => 'అసర్', 'maghrib' => 'మగ్రిబ్', 'isha' => 'ఇషా'])

@section('content')
    <div class="page-head">
        <div>
            <h1>Prayer times</h1>
            <span class="muted">{{ $date->format('l j F Y') }} &middot; <span lang="te">{{ $date->locale('te')->dayName }}</span>
                &middot; {{ $schedule->hijri->format() }}
                @if ($date->equalTo($today)) <span class="badge confirmed">today</span>@endif</span>
        </div>
        <div class="actions">
            <a class="btn secondary" href="{{ route('admin.prayer-times.edit', ['date' => $date->subDay()->toDateString()]) }}">&larr; Previous day</a>
            @unless ($date->equalTo($today))
                <a class="btn secondary" href="{{ route('admin.prayer-times.edit') }}">Today</a>
            @endunless
            <a class="btn secondary" href="{{ route('admin.prayer-times.edit', ['date' => $date->addDay()->toDateString()]) }}">Next day &rarr;</a>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.prayer-times.edit') }}" class="actions" style="align-items:end;margin-bottom:16px">
        <div class="field"><label for="pick-date">Go to date</label><input id="pick-date" name="date" type="date" value="{{ $date->toDateString() }}"></div>
        <button class="btn secondary" type="submit">Open</button>
    </form>

    <div class="card">
        <div class="page-head" style="margin-bottom:8px">
            <h2 style="margin:0">Adhan &amp; Iqamah</h2>
            @if ($edited->isNotEmpty())
                <form method="POST" action="{{ route('admin.prayer-times.reset') }}" class="inline" onsubmit="return confirm('Use the calculated times for this day?')">
                    @csrf @method('DELETE')
                    <input type="hidden" name="date" value="{{ $date->toDateString() }}">
                    <button class="btn small secondary" type="submit">Reset this day to calculated</button>
                </form>
            @endif
        </div>
        <p class="muted">Change a time and press Save. The change applies to this date only, everywhere: the website, the dashboard and the app.
            Leave the iqamah as it is to keep following your <a href="{{ route('admin.settings.edit') }}">iqamah rules</a> (it moves with a changed adhan).
            Clear a box, or type the value shown under it, to go back.</p>

        <form method="POST" action="{{ route('admin.prayer-times.update') }}">
            @csrf @method('PUT')
            <input type="hidden" name="date" value="{{ $date->toDateString() }}">
            <div class="table-wrap admin-days">
                <table>
                    <thead><tr><th>Prayer</th><th>Adhan <small lang="te">అజాన్</small></th><th>Iqamah <small lang="te">ఇఖామత్</small></th><th></th></tr></thead>
                    <tbody>
                    @foreach ($prayers as $p)
                        @php($o = $edited->get($p->value))
                        <tr>
                            <td><strong>{{ $p === \App\Enums\Prayer::Dhuhr && $date->isFriday() ? 'Jumuah' : $p->label() }}</strong> <span lang="te" class="muted">{{ $names_te[$p->value] }}</span></td>
                            <td>
                                <input type="time" name="adhan[{{ $p->value }}]" value="{{ $schedule->times[$p->value]?->format('H:i') }}" @class(['custom' => $o?->adhan]) aria-label="{{ $p->label() }} adhan">
                                <small class="muted">calc. {{ $calculated->times[$p->value]?->format('H:i') ?? '—' }}</small>
                            </td>
                            <td>
                                <input type="time" name="iqamah[{{ $p->value }}]" value="{{ $schedule->iqamah[$p->value]?->format('H:i') }}" @class(['custom' => $o?->iqamah]) aria-label="{{ $p->label() }} iqamah">
                                <small class="muted">rule {{ $calculated->iqamah[$p->value]?->format('H:i') ?? '—' }}</small>
                            </td>
                            <td>@if ($o)<span class="badge pending">edited</span>@endif</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="actions" style="margin-top:12px"><button class="btn" type="submit">Save times</button></div>
        </form>
    </div>
@endsection
