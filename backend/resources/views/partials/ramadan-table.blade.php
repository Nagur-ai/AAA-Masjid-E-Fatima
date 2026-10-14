{{-- Ramadan timetable like the masjid's printed one: No., date, day, sehri and iftar, grouped by ashra.
     Fridays green, other days red, today gold. --}}
@php($todayStr ??= null)
@php($title ??= null)
@php($first = $days[0] ?? null)
@php($ashras = [
    1 => ['మొదటి అష్రా · రహ్మత్', 'First Ashra · Mercy'],
    2 => ['రెండవ అష్రా · మగ్ఫిరత్', 'Second Ashra · Forgiveness'],
    3 => ['మూడవ అష్రా · నజాత్', 'Third Ashra · Salvation'],
])
<div class="ramadan-sheet">
    <div class="ramadan-banner">
        <svg class="crescent" viewBox="0 0 64 64" aria-hidden="true">
            <path fill="currentColor" d="M40 6a26 26 0 1 0 18 44.6A22 22 0 1 1 40 6Z"/>
            <path fill="currentColor" d="m50 14 1.8 4.6 4.9.3-3.8 3.1 1.2 4.8-4.1-2.7-4.1 2.7 1.2-4.8-3.8-3.1 4.9-.3Z"/>
        </svg>
        <div>
            @if ($title)<div class="banner-masjid">{{ $title }}</div>@endif
            <div class="banner-title" lang="te">రంజాన్ టైం టేబుల్
                @if ($first) {{ \Carbon\Carbon::parse($first['date'])->format('Y') }}@endif
            </div>
            @if ($first)<div class="banner-sub">{{ $first['hijri']['year'] }} AH</div>@endif
        </div>
    </div>
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
                @if ($loop->first || $d['ashra'] !== $days[$loop->index - 1]['ashra'])
                    <tr class="ashra-row ashra-{{ $d['ashra'] }}">
                        <td colspan="5"><span lang="te">{{ $ashras[$d['ashra']][0] }}</span> <small>{{ $ashras[$d['ashra']][1] }}</small></td>
                    </tr>
                @endif
                @php($isFriday = $d['weekday'] === 'Friday')
                @php($isToday = $d['date'] === $todayStr)
                <tr @class(['friday' => $isFriday, 'today' => $isToday])>
                    <td class="roza">{{ $d['day'] }}</td>
                    <td>{{ \Carbon\Carbon::parse($d['date'])->format('j-n-y') }}</td>
                    <td lang="te">
                        {{ $d['weekday_te'] }}
                        @if ($isToday)<span class="pill today-pill">ఈరోజు</span>@elseif ($isFriday)<span class="pill friday-pill">జుమ్మా</span>@endif
                    </td>
                    <td><span class="time-chip">{{ $d['sehri_ends'] }}</span></td>
                    <td><span class="time-chip">{{ $d['iftar'] }}</span></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
<p class="ramadan-legend">
    <span class="swatch friday"></span> <span lang="te">శుక్రవారం</span> (Jumuah)
    <span class="swatch other"></span> {{ __('Other days') }}
    @if ($todayStr && collect($days)->contains('date', $todayStr)) <span class="swatch today"></span> <span lang="te">ఈరోజు</span> ({{ __('Today') }}) @endif
</p>
