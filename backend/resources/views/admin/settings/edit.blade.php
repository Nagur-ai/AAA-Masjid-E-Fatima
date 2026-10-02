@extends('layouts.admin')

@section('title', 'Settings')

@section('content')
    <div class="page-head"><h1>Masjid settings</h1></div>

    <form method="POST" action="{{ route('admin.settings.update') }}" class="card" style="margin-bottom:16px">
        @csrf
        @method('PUT')
        <h2>Details</h2>
        <div class="form-grid">
            <div class="field"><label for="name">Name</label><input id="name" name="name" value="{{ old('name', $masjid->name) }}" required></div>
            <div class="field"><label for="phone">Phone</label><input id="phone" name="phone" value="{{ old('phone', $masjid->phone) }}"></div>
            <div class="field"><label for="email">Email</label><input id="email" name="email" type="email" value="{{ old('email', $masjid->email) }}"></div>
            <div class="field"><label for="address">Address</label><input id="address" name="address" value="{{ old('address', $masjid->address) }}"></div>
            <div class="field"><label for="city">City</label><input id="city" name="city" value="{{ old('city', $masjid->city) }}"></div>
            <div class="field"><label for="country">Country (ISO-2)</label><input id="country" name="country" maxlength="2" value="{{ old('country', $masjid->country) }}"></div>
            <div class="field"><label for="currency">Currency (ISO-3)</label><input id="currency" name="currency" maxlength="3" value="{{ old('currency', $masjid->currency) }}" required></div>
            <div class="field full"><label for="bank_details">Bank / UPI details shown to donors</label>
                <textarea id="bank_details" name="bank_details" rows="3">{{ old('bank_details', $masjid->bank_details) }}</textarea></div>
        </div>

        <h2 style="margin-top:16px">Prayer time calculation</h2>
        <div class="form-grid">
            <div class="field"><label for="latitude">Latitude</label><input id="latitude" name="latitude" type="number" step="0.000001" value="{{ old('latitude', $masjid->latitude) }}" required></div>
            <div class="field"><label for="longitude">Longitude</label><input id="longitude" name="longitude" type="number" step="0.000001" value="{{ old('longitude', $masjid->longitude) }}" required></div>
            <div class="field"><label for="elevation" title="Only for a masjid elevated above its surrounding horizon (e.g. on a hill). Leave 0 in a city on a plateau.">Elevation above horizon (m)</label><input id="elevation" name="elevation" type="number" value="{{ old('elevation', $masjid->elevation) }}"></div>
            <div class="field"><label for="timezone">Timezone</label>
                <select id="timezone" name="timezone">
                    @foreach ($timezones as $tz)
                        <option @selected(old('timezone', $masjid->timezone) === $tz)>{{ $tz }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field"><label for="calculation_method">Method</label>
                <select id="calculation_method" name="calculation_method">
                    @foreach ($methods as $m)
                        <option value="{{ $m->value }}" @selected(old('calculation_method', $masjid->calculation_method->value) === $m->value)>{{ $m->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field"><label for="asr_method">Asr</label>
                <select id="asr_method" name="asr_method">
                    @foreach ($asrMethods as $m)
                        <option value="{{ $m->value }}" @selected(old('asr_method', $masjid->asr_method->value) === $m->value)>{{ $m->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field"><label for="high_latitude_rule">High latitude rule</label>
                <select id="high_latitude_rule" name="high_latitude_rule">
                    @foreach ($highLatitudeRules as $m)
                        <option value="{{ $m->value }}" @selected(old('high_latitude_rule', $masjid->high_latitude_rule->value) === $m->value)>{{ $m->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field"><label for="hijri_adjustment">Hijri adjustment (days)</label>
                <select id="hijri_adjustment" name="hijri_adjustment">
                    @foreach ([-2, -1, 0, 1, 2] as $n)
                        <option value="{{ $n }}" @selected((int) old('hijri_adjustment', $masjid->hijri_adjustment) === $n)>{{ $n > 0 ? '+'.$n : $n }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field"><label for="imsak_minutes_before_fajr">Imsak (min before Fajr)</label>
                <input id="imsak_minutes_before_fajr" name="imsak_minutes_before_fajr" type="number" min="0" max="30" value="{{ old('imsak_minutes_before_fajr', $masjid->imsak_minutes_before_fajr) }}"></div>
        </div>
        <div style="margin-top:12px"><button class="btn" type="submit">Save settings</button></div>
    </form>

    <div class="grid grid-2">
        <div class="card">
            <h2>Iqamah times</h2>
            <p class="muted">The newest rule in effect for a date wins. Add a new rule with a start date for seasonal changes.</p>
            <div class="table-wrap" style="margin-bottom:12px">
                <table>
                    <thead><tr><th>Prayer</th><th>Rule</th><th>From</th><th>To</th><th></th></tr></thead>
                    <tbody>
                    @foreach ($prayers as $p)
                        @foreach ($rules[$p->value] ?? [] as $r)
                            <tr>
                                <td>{{ $p->label() }}</td>
                                <td>{{ $r->mode === \App\Enums\IqamahMode::Fixed ? 'at '.substr($r->fixed_time, 0, 5) : '+'.$r->offset_minutes.' min'.($r->round_to_minutes ? ', round up to '.$r->round_to_minutes : '') }}</td>
                                <td>{{ $r->effective_from->format('j M Y') }}</td>
                                <td>{{ $r->effective_to?->format('j M Y') ?? '—' }}</td>
                                <td>
                                    <form method="POST" action="{{ route('admin.settings.iqamah.destroy', $r) }}" class="inline" onsubmit="return confirm('Remove this rule?')">
                                        @csrf @method('DELETE')
                                        <button class="btn small secondary" type="submit">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                    </tbody>
                </table>
            </div>
            <form method="POST" action="{{ route('admin.settings.iqamah.store') }}" class="form-grid">
                @csrf
                <div class="field"><label for="prayer">Prayer</label>
                    <select id="prayer" name="prayer">@foreach ($prayers as $p)<option value="{{ $p->value }}">{{ $p->label() }}</option>@endforeach</select>
                </div>
                <div class="field"><label for="mode">Mode</label>
                    <select id="mode" name="mode"><option value="offset">Minutes after adhan</option><option value="fixed">Fixed time</option></select>
                </div>
                <div class="field"><label for="offset_minutes">Minutes after adhan</label><input id="offset_minutes" name="offset_minutes" type="number" min="0" max="120"></div>
                <div class="field"><label for="round_to_minutes">Round up to</label>
                    <select id="round_to_minutes" name="round_to_minutes"><option value="0">No rounding</option><option>5</option><option>10</option><option>15</option></select>
                </div>
                <div class="field"><label for="fixed_time">Fixed time</label><input id="fixed_time" name="fixed_time" type="time"></div>
                <div class="field"><label for="effective_from">Effective from</label><input id="effective_from" name="effective_from" type="date" value="{{ now($masjid->timezone)->toDateString() }}" required></div>
                <div class="field"><label for="effective_to">Until (optional)</label><input id="effective_to" name="effective_to" type="date"></div>
                <div class="field full"><button class="btn secondary" type="submit">Add iqamah rule</button></div>
            </form>
        </div>

        <div class="card">
            <h2>Jumuah sessions</h2>
            @foreach ($jumuah as $s)
                <div class="actions" style="align-items:center;margin-bottom:8px">
                    <strong>{{ $s->label }}</strong>{{ $s->language ? " ({$s->language})" : '' }}: khutbah {{ substr($s->khutbah_time, 0, 5) }}, iqamah {{ substr($s->iqamah_time, 0, 5) }}
                    <form method="POST" action="{{ route('admin.settings.jumuah.destroy', $s) }}" class="inline">
                        @csrf @method('DELETE')
                        <button class="btn small secondary" type="submit">Remove</button>
                    </form>
                </div>
            @endforeach
            <form method="POST" action="{{ route('admin.settings.jumuah.store') }}" class="form-grid">
                @csrf
                <div class="field"><label for="label">Label</label><input id="label" name="label" value="Jumuah" required></div>
                <div class="field"><label for="language">Language</label><input id="language" name="language"></div>
                <div class="field"><label for="khutbah_time">Khutbah</label><input id="khutbah_time" name="khutbah_time" type="time" required></div>
                <div class="field"><label for="iqamah_time">Iqamah</label><input id="iqamah_time" name="iqamah_time" type="time" required></div>
                <div class="field full"><button class="btn secondary" type="submit">Add session</button></div>
            </form>
        </div>
    </div>
@endsection
