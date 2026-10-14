@php($sfx = $session?->id ?? 'new')
<form method="POST" action="{{ $action }}" class="form-grid" style="margin-top:8px">
    @csrf
    @method($method)
    <div class="field"><label for="label-{{ $sfx }}">Label</label><input id="label-{{ $sfx }}" name="label" value="{{ $session?->label ?? 'Jumuah' }}" required></div>
    <div class="field"><label for="language-{{ $sfx }}">Language</label><input id="language-{{ $sfx }}" name="language" value="{{ $session?->language }}"></div>
    <div class="field"><label for="khutbah-{{ $sfx }}">Khutbah</label><input id="khutbah-{{ $sfx }}" name="khutbah_time" type="time" value="{{ $session ? substr($session->khutbah_time, 0, 5) : '' }}" required></div>
    <div class="field"><label for="iqamah-{{ $sfx }}">Iqamah</label><input id="iqamah-{{ $sfx }}" name="iqamah_time" type="time" value="{{ $session ? substr($session->iqamah_time, 0, 5) : '' }}" required></div>
    <div class="field full"><button class="btn secondary" type="submit">{{ $session ? 'Save session' : 'Add session' }}</button></div>
</form>
