{{-- Ramadan timetable like the masjid's printed one: date, day, sehri and iftar. Fridays green, other days red. --}}
@php($todayStr ??= null)
<div class="table-wrap ramadan-days">
    <table class="ramadan-table">
        <thead>
        <tr>
            <th><span lang="te">క్ర.సం</span><small>No.</small></th>
            <th><span lang="te">తారీఖ్</span><small>Date</small></th>
            <th><span lang="te">రోజ్</span><small>Day</small></th>
            <th><span lang="te">సహరీ</span><small>Sehri</small></th>
            <th><span lang="te">ఇఫ్తార్</span><small>Iftar</small></th>
        </tr>
        </thead>
        <tbody>
        @foreach ($days as $d)
            <tr @class(['friday' => $d['weekday'] === 'Friday', 'today' => $d['date'] === $todayStr])>
                <td>{{ $d['day'] }}</td>
                <td>{{ \Carbon\Carbon::parse($d['date'])->format('j-n-y') }}</td>
                <td lang="te">{{ $d['weekday_te'] }}</td>
                <td class="time">{{ $d['sehri_ends'] }}</td>
                <td class="time">{{ $d['iftar'] }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
<p class="ramadan-legend">
    <span class="swatch friday"></span> <span lang="te">శుక్రవారం</span> (Jumuah)
    <span class="swatch other"></span> Other days
    @if ($todayStr && collect($days)->contains('date', $todayStr)) <span class="swatch today"></span> Today @endif
</p>
