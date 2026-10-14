@php($sfx = $rule?->id ?? 'new')
<form method="POST" action="{{ $action }}" class="form-grid" style="margin-top:8px">
    @csrf
    @method($method)
    <div class="field"><label for="prayer-{{ $sfx }}">Prayer</label>
        <select id="prayer-{{ $sfx }}" name="prayer">
            @foreach (\App\Enums\Prayer::cases() as $p)<option value="{{ $p->value }}" @selected($rule?->prayer === $p)>{{ $p->label() }}</option>@endforeach
        </select>
    </div>
    <div class="field"><label for="mode-{{ $sfx }}">Mode</label>
        <select id="mode-{{ $sfx }}" name="mode">
            <option value="offset" @selected($rule?->mode === \App\Enums\IqamahMode::Offset)>Minutes after adhan</option>
            <option value="fixed" @selected($rule?->mode === \App\Enums\IqamahMode::Fixed)>Fixed time</option>
        </select>
    </div>
    <div class="field"><label for="offset-{{ $sfx }}">Minutes after adhan</label><input id="offset-{{ $sfx }}" name="offset_minutes" type="number" min="0" max="120" value="{{ $rule?->offset_minutes }}"></div>
    <div class="field"><label for="round-{{ $sfx }}">Round up to</label>
        <select id="round-{{ $sfx }}" name="round_to_minutes">
            @foreach ([0 => 'No rounding', 5 => '5', 10 => '10', 15 => '15'] as $v => $l)<option value="{{ $v }}" @selected((int) $rule?->round_to_minutes === $v)>{{ $l }}</option>@endforeach
        </select>
    </div>
    <div class="field"><label for="fixed-{{ $sfx }}">Fixed time</label><input id="fixed-{{ $sfx }}" name="fixed_time" type="time" value="{{ $rule?->fixed_time ? substr($rule->fixed_time, 0, 5) : '' }}"></div>
    <div class="field"><label for="from-{{ $sfx }}">Effective from</label><input id="from-{{ $sfx }}" name="effective_from" type="date" value="{{ $rule?->effective_from?->toDateString() ?? now($currentMasjid->timezone)->toDateString() }}" required></div>
    <div class="field"><label for="to-{{ $sfx }}">Until (optional)</label><input id="to-{{ $sfx }}" name="effective_to" type="date" value="{{ $rule?->effective_to?->toDateString() }}"></div>
    <div class="field full"><button class="btn secondary" type="submit">{{ $rule ? 'Save rule' : 'Add iqamah rule' }}</button></div>
</form>
