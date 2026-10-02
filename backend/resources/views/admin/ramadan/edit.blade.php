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
@endsection
