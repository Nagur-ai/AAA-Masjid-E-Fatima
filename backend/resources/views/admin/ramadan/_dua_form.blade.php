<form method="POST" action="{{ $action }}" class="form-grid" style="margin-top:8px">
    @csrf
    @method($method)
    <div class="field"><label>Type</label>
        <select name="type">
            @foreach (\App\Models\RamadanDua::TYPES as $value => $label)
                <option value="{{ $value }}" @selected(($dua?->type ?? 'other') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="field"><label>Heading</label><input name="title" lang="te" value="{{ $dua?->title }}" maxlength="120" required placeholder="సహరీ దుఆ"></div>
    <div class="field"><label>Order</label><input name="sort_order" type="number" min="0" max="999" value="{{ $dua?->sort_order }}" placeholder="last"></div>
    <div class="field full"><label>Dua (Telugu)</label><textarea name="body" lang="te" rows="3" maxlength="2000" required>{{ $dua?->body }}</textarea></div>
    <label class="check"><input type="checkbox" name="is_active" value="1" @checked($dua?->is_active ?? true)> Show</label>
    <div class="field full"><button class="btn secondary" type="submit">{{ $dua ? 'Save dua' : 'Add dua' }}</button></div>
</form>
