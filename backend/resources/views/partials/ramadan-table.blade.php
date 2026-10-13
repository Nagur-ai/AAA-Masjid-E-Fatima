{{-- Ramadan timetable: one row per roza, each prayer in its own bordered column, Fridays highlighted. --}}
@php($todayStr ??= null)
<div class="table-wrap ramadan-days">
    <table class="ramadan-table">
        <thead>
        <tr>
            <th>Roza</th>
            <th>Date</th>
            <th lang="te">వారం</th>
            <th class="p-col p-sehri">Sehri <small lang="te">సహరీ</small></th>
            <th class="p-col p-fajr">Fajr <small lang="te">ఫజర్</small></th>
            <th class="p-col p-dhuhr">Dhuhr <small lang="te">జుహర్</small></th>
            <th class="p-col p-asr">Asr <small lang="te">అసర్</small></th>
            <th class="p-col p-iftar">Iftar <small lang="te">ఇఫ్తార్</small></th>
            <th class="p-col p-isha">Isha <small lang="te">ఇషా</small></th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @foreach ($days as $d)
            @php($isFriday = $d['weekday'] === 'Friday')
            <tr @class(['friday' => $isFriday, 'today' => $d['date'] === $todayStr])>
                <td><strong>{{ $d['day'] }}</strong></td>
                <td>{{ \Carbon\Carbon::parse($d['date'])->format('D j M') }}</td>
                <td lang="te">{{ $d['weekday_te'] }}@if ($isFriday) <span class="badge jumuah">Jumuah</span>@endif</td>
                <td class="p-col p-sehri"><strong>{{ $d['sehri_ends'] }}</strong> <small>imsak {{ $d['imsak'] }}</small></td>
                <td class="p-col p-fajr">{{ $d['times']['fajr'] }}</td>
                <td class="p-col p-dhuhr">{{ $d['times']['dhuhr'] }}</td>
                <td class="p-col p-asr">{{ $d['times']['asr'] }}</td>
                <td class="p-col p-iftar"><strong>{{ $d['iftar'] }}</strong></td>
                <td class="p-col p-isha">{{ $d['times']['isha'] }}</td>
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
<p class="muted ramadan-legend"><span class="swatch friday"></span> Friday (Jumuah) <span class="swatch today"></span> Today</p>
