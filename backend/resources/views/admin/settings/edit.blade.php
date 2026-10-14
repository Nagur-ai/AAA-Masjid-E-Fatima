@extends('layouts.admin')

@section('title', 'Settings')

@section('content')
    <div class="page-head"><h1>Masjid settings</h1></div>

    <div class="card" style="margin-bottom:16px">
        <h2>Banner photo</h2>
        <p class="muted">A photo of the masjid, shown behind its name and today's date on the public home page. Crop out phone buttons and people before uploading. JPG, PNG or WebP, up to 5 MB.</p>
        @if ($masjid->banner_image_path)
            <div class="hero hero-photo banner-preview" style="background-image: linear-gradient(180deg, rgba(8, 48, 36, .30), rgba(8, 48, 36, .85)), url('{{ route('masjid.banner', $masjid) }}'); background-position: center {{ $masjid->banner_position }};">
                <h1 style="margin:0">{{ $masjid->name }}</h1>
                <div class="muted">Preview</div>
            </div>
            <form method="POST" action="{{ route('admin.settings.banner.destroy') }}" class="inline" onsubmit="return confirm('Remove the banner photo?')">
                @csrf @method('DELETE')
                <button class="btn small danger" type="submit">Remove photo</button>
            </form>
        @endif
        <form method="POST" action="{{ route('admin.settings.banner.store') }}" enctype="multipart/form-data" class="form-grid" style="margin-top:12px">
            @csrf
            <div class="field"><label for="banner">{{ $masjid->banner_image_path ? 'Replace photo' : 'Upload photo' }}</label>
                <input id="banner" name="banner" type="file" accept="image/jpeg,image/png,image/webp" @required(! $masjid->banner_image_path)></div>
            <div class="field"><label for="banner_position">Part of the photo to show</label>
                <select id="banner_position" name="banner_position">
                    @foreach (['top' => 'Top (e.g. the minaret)', 'center' => 'Middle', 'bottom' => 'Bottom (e.g. the entrance)'] as $v => $l)
                        <option value="{{ $v }}" @selected($masjid->banner_position === $v)>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field full"><button class="btn secondary" type="submit">Save banner</button></div>
        </form>
    </div>

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
            <p class="muted">The newest rule in effect for a date wins. Add a new rule with a start date for seasonal changes.
                To change the adhan or iqamah of a single day, use <a href="{{ route('admin.prayer-times.edit') }}">Prayer times for a day</a>.</p>
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
                                    <div class="actions">
                                        <form method="POST" action="{{ route('admin.settings.iqamah.destroy', $r) }}" class="inline" onsubmit="return confirm('Remove this rule?')">
                                            @csrf @method('DELETE')
                                            <button class="btn small danger" type="submit">Remove</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <tr class="edit-row">
                                <td colspan="5">
                                    <details>
                                        <summary>Edit {{ $p->label() }} rule</summary>
                                        @include('admin.settings._iqamah_form', ['rule' => $r, 'action' => route('admin.settings.iqamah.update', $r), 'method' => 'PUT'])
                                    </details>
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                    </tbody>
                </table>
            </div>
            <h3>Add an iqamah rule</h3>
            @include('admin.settings._iqamah_form', ['rule' => null, 'action' => route('admin.settings.iqamah.store'), 'method' => 'POST'])
        </div>

        <div class="card">
            <h2>Jumuah sessions</h2>
            @foreach ($jumuah as $s)
                <div class="list-item">
                    <div class="actions" style="align-items:center;justify-content:space-between">
                        <span><strong>{{ $s->label }}</strong>{{ $s->language ? " ({$s->language})" : '' }}: khutbah {{ substr($s->khutbah_time, 0, 5) }}, iqamah {{ substr($s->iqamah_time, 0, 5) }}</span>
                        <form method="POST" action="{{ route('admin.settings.jumuah.destroy', $s) }}" class="inline" onsubmit="return confirm('Remove this Jumuah session?')">
                            @csrf @method('DELETE')
                            <button class="btn small danger" type="submit">Remove</button>
                        </form>
                    </div>
                    <details>
                        <summary>Edit</summary>
                        @include('admin.settings._jumuah_form', ['session' => $s, 'action' => route('admin.settings.jumuah.update', $s), 'method' => 'PUT'])
                    </details>
                </div>
            @endforeach
            <h3>Add a session</h3>
            @include('admin.settings._jumuah_form', ['session' => null, 'action' => route('admin.settings.jumuah.store'), 'method' => 'POST'])
        </div>
    </div>
@endsection
