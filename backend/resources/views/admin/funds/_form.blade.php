<form method="POST" action="{{ $action }}" class="form-grid" style="margin-top:8px">
    @csrf
    @method($method)
    <div class="field"><label>Name</label><input name="name" value="{{ $fund?->name }}" required></div>
    <div class="field"><label>Type</label>
        <select name="type">
            @foreach (\App\Enums\FundType::cases() as $t)
                <option value="{{ $t->value }}" @selected($fund?->type === $t)>{{ $t->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="field"><label>Target amount</label><input name="target_amount" type="number" min="0" step="0.01" value="{{ $fund?->target_amount }}"></div>
    <div class="field full"><label>Description</label><input name="description" value="{{ $fund?->description }}"></div>
    <label class="check"><input type="checkbox" name="is_restricted" value="1" @checked($fund?->is_restricted)> Restricted</label>
    <label class="check"><input type="checkbox" name="is_active" value="1" @checked($fund?->is_active ?? true)> Active</label>
    <div class="field full"><button class="btn secondary" type="submit">{{ $fund ? 'Save' : 'Create fund' }}</button></div>
</form>
