@extends('layouts.admin')

@section('title', 'Ramadan '.$hijriYear)

@section('content')
    <div class="page-head">
        <div>
            <h1>Ramadan {{ $hijriYear }} AH</h1>
            <span class="badge {{ $calendar->exists ? ($calendar->is_confirmed ? 'confirmed' : 'pending') : 'estimated' }}">
                {{ $calendar->exists ? ($calendar->is_confirmed ? 'confirmed' : 'saved, not confirmed') : 'estimate (not saved)' }}
            </span>
        </div>
        <div class="actions">
            <a class="btn secondary" href="{{ route('admin.ramadan.edit', $hijriYear - 1) }}">&larr; {{ $hijriYear - 1 }}</a>
            <a class="btn secondary" href="{{ route('admin.ramadan.edit', $hijriYear + 1) }}">{{ $hijriYear + 1 }} &rarr;</a>
            <a class="btn secondary" href="{{ route('masjid.ramadan', [$currentMasjid, $hijriYear]) }}">View calendar</a>
        </div>
    </div>

    <p class="muted">The start date is estimated from the Umm al-Qura calendar (with your Hijri adjustment). After the moon is sighted, set the actual first day and tick “confirmed”. Sehri and iftar times are calculated from your prayer settings.</p>

    <form method="POST" action="{{ route('admin.ramadan.update', $hijriYear) }}" class="card">
        @csrf
        @method('PUT')
        <h2>Calendar</h2>
        <div class="form-grid">
            <div class="field"><label for="starts_on">First day of Ramadan</label>
                <input id="starts_on" name="starts_on" type="date" value="{{ old('starts_on', $calendar->starts_on->toDateString()) }}" required></div>
            <div class="field"><label for="total_days">Number of days</label>
                <select id="total_days" name="total_days">
                    @foreach ([29, 30] as $n)<option @selected((int) old('total_days', $calendar->total_days) === $n)>{{ $n }}</option>@endforeach
                </select>
            </div>
            <div class="field"><label for="taraweeh_time">Taraweeh time</label>
                <input id="taraweeh_time" name="taraweeh_time" type="time" value="{{ old('taraweeh_time', $calendar->taraweeh_time ? substr($calendar->taraweeh_time, 0, 5) : '') }}"></div>
            <div class="field full"><label for="notes">Notes for the community (iftar programme, i'tikaf, Zakat al-Fitr amount…)</label>
                <textarea id="notes" name="notes" rows="3">{{ old('notes', $calendar->notes) }}</textarea></div>
            <label class="check full"><input type="checkbox" name="is_confirmed" value="1" @checked(old('is_confirmed', $calendar->is_confirmed))> Start confirmed by moon sighting / announcement</label>
            <div class="field full"><button class="btn" type="submit">Save</button></div>
        </div>
    </form>

    @php($isEdited = fn ($d) => $d['date_custom'] || $d['day_label_custom'] || $d['sehri_custom'] || $d['iftar_custom'])
    @php($edited = collect($timetable['days'])->filter($isEdited)->count())
    <div class="card" style="margin-top:16px" id="timetable">
        <div class="page-head" style="margin-bottom:8px">
            <div>
                <h2 style="margin:0"><span lang="te">రంజాన్ టైం టేబుల్</span> <small class="muted">· Daily date, day and sehri &amp; iftar times</small></h2>
                <span class="muted">{{ \Carbon\Carbon::parse($timetable['starts_on'])->format('j M Y') }} – {{ \Carbon\Carbon::parse($timetable['ends_on'])->format('j M Y') }}
                    &middot; {{ $timetable['total_days'] }} days &middot; {{ $edited }} {{ $edited === 1 ? 'day' : 'days' }} edited</span>
            </div>
            @if ($edited)
                <form method="POST" action="{{ route('admin.ramadan.days.reset', $hijriYear) }}" class="inline" onsubmit="return confirm('Use the calculated times for every day?')">
                    @csrf @method('DELETE')
                    <button class="btn small secondary" type="submit">Reset all to calculated</button>
                </form>
            @endif
        </div>
        <p class="muted">Everything below can be changed: the date, the day name in Telugu (రోజ్, pick from the list) and the sehri and iftar times. Type your masjid's own timetable and press Save. A day you change is marked <span class="badge pending">edited</span>; clear a box (or type the calculated value shown under it) to go back. With the day set to “ఆటో”, changing a date also changes its day name. To move every date at once, change “First day of Ramadan” above.</p>

        <form method="POST" action="{{ route('admin.ramadan.days.update', $hijriYear) }}">
            @csrf @method('PUT')
            <div class="table-wrap admin-days">
                <table>
                    <thead><tr><th>Roza</th><th>Date <small lang="te">తారీఖ్</small></th><th>Day <small lang="te">రోజ్</small></th><th>Sehri <small lang="te">సహరీ</small></th><th>Iftar <small lang="te">ఇఫ్తార్</small></th><th></th></tr></thead>
                    <tbody>
                    @foreach ($timetable['days'] as $d)
                        <tr @class(['friday' => $d['weekday'] === 'Friday'])>
                            <td><strong>{{ $d['day'] }}</strong></td>
                            <td>
                                <input type="date" name="date[{{ $d['day'] }}]" value="{{ $d['date'] }}" @class(['custom' => $d['date_custom']]) aria-label="Date, day {{ $d['day'] }}">
                                <small class="muted">calc. {{ \Carbon\Carbon::parse($d['date_calculated'])->format('D j M') }}</small>
                            </td>
                            <td>
                                @php($picked = $d['day_label_custom'] ? $d['weekday_te'] : '')
                                <select name="day_label[{{ $d['day'] }}]" lang="te" @class(['day-input', 'custom' => $d['day_label_custom']]) aria-label="Day name in Telugu, day {{ $d['day'] }}">
                                    <option value="">ఆటో · {{ \Carbon\Carbon::parse($d['date'])->locale('te')->dayName }}</option>
                                    <optgroup label="పూర్తి పేరు · Full">
                                        @foreach (\App\Models\RamadanDayTime::DAY_NAMES as $name)
                                            <option value="{{ $name }}" @selected($picked === $name)>{{ $name }}</option>
                                        @endforeach
                                    </optgroup>
                                    <optgroup label="చిన్న పేరు · Short">
                                        @foreach (\App\Models\RamadanDayTime::DAY_NAMES_SHORT as $name)
                                            <option value="{{ $name }}" @selected($picked === $name)>{{ $name }}</option>
                                        @endforeach
                                    </optgroup>
                                </select>
                                <small class="muted">Auto follows the date</small>
                            </td>
                            <td>
                                <input type="time" name="sehri[{{ $d['day'] }}]" value="{{ $d['sehri_ends'] }}" @class(['custom' => $d['sehri_custom']]) aria-label="Sehri, day {{ $d['day'] }}">
                                <small class="muted">calc. {{ $d['sehri_calculated'] }}</small>
                            </td>
                            <td>
                                <input type="time" name="iftar[{{ $d['day'] }}]" value="{{ $d['iftar'] }}" @class(['custom' => $d['iftar_custom']]) aria-label="Iftar, day {{ $d['day'] }}">
                                <small class="muted">calc. {{ $d['iftar_calculated'] }}</small>
                            </td>
                            <td>@if ($isEdited($d))<span class="badge pending">edited</span>@endif</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="actions" style="margin-top:12px"><button class="btn" type="submit">Save timetable</button></div>
        </form>
    </div>

    <div class="card" style="margin-top:16px">
        <h2 lang="te">సహరీ & ఇఫ్తార్ దుఆలు <small class="muted">· Duas</small></h2>
        <p class="muted">Shown above the Ramadan calendar on the website, the dashboard and the app. Sehri duas are purple, iftar duas green. Untick “Show” to hide a dua without deleting it.</p>

        @forelse ($duas as $dua)
            <div @class(['dua', 'dua-'.$dua->type, 'dua-hidden' => ! $dua->is_active]) style="margin-bottom:12px">
                <div class="page-head" style="margin-bottom:0">
                    <div lang="te">
                        <strong class="dua-title">{{ $dua->title }}</strong>
                        <span class="badge">{{ \App\Models\RamadanDua::TYPES[$dua->type] ?? $dua->type }}</span>
                        @unless ($dua->is_active)<span class="badge cancelled">hidden</span>@endunless
                    </div>
                    <form method="POST" action="{{ route('admin.ramadan.duas.destroy', $dua) }}" class="inline" onsubmit="return confirm('Delete this dua?')">
                        @csrf @method('DELETE')
                        <button class="btn small danger" type="submit">Delete</button>
                    </form>
                </div>
                <p class="dua-telugu" lang="te">{{ $dua->body }}</p>
                <details>
                    <summary>Edit</summary>
                    @include('admin.ramadan._dua_form', ['dua' => $dua, 'action' => route('admin.ramadan.duas.update', $dua), 'method' => 'PUT'])
                </details>
            </div>
        @empty
            <p class="muted">No duas yet. Add one below.</p>
        @endforelse

        <h3>Add a dua</h3>
        @include('admin.ramadan._dua_form', ['dua' => null, 'action' => route('admin.ramadan.duas.store'), 'method' => 'POST'])
    </div>

    <div class="card" style="margin-top:16px">
        <h2>Printed timetable image</h2>
        <p class="muted">Optional. Shown at the top of the Ramadan page, the dashboard and the app. JPG, PNG or WebP, up to 5 MB.</p>
        @if ($currentMasjid->ramadan_image_path)
            <a class="timetable-image" href="{{ route('masjid.ramadan.image', $currentMasjid) }}" target="_blank" rel="noopener" style="max-width:360px;margin:0 0 12px">
                <img src="{{ route('masjid.ramadan.image', $currentMasjid) }}" alt="Current timetable image">
            </a>
            <form method="POST" action="{{ route('admin.ramadan.image.destroy') }}" class="inline" onsubmit="return confirm('Remove the timetable image?')">
                @csrf @method('DELETE')
                <button class="btn small danger" type="submit">Remove image</button>
            </form>
        @endif
        <form method="POST" action="{{ route('admin.ramadan.image.store') }}" enctype="multipart/form-data" class="form-grid" style="margin-top:12px">
            @csrf
            <div class="field"><label for="image">{{ $currentMasjid->ramadan_image_path ? 'Replace image' : 'Upload image' }}</label>
                <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp" required></div>
            <div class="field full"><button class="btn secondary" type="submit">Upload</button></div>
        </form>
    </div>
@endsection
