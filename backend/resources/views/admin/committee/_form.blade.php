@php($sfx = $member?->id ?? 'new')
<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="form-grid" style="margin-top:8px">
    @csrf
    @method($method)
    <div class="field"><label for="name-{{ $sfx }}">Name</label><input id="name-{{ $sfx }}" name="name" value="{{ $member?->name }}" maxlength="120" required></div>
    <div class="field"><label for="role-{{ $sfx }}">Role</label>
        <select id="role-{{ $sfx }}" name="role" onchange="document.getElementById('title-field-{{ $sfx }}').hidden = this.value !== 'other'">
            @foreach (\App\Models\CommitteeMember::ROLES as $value => $label)
                <option value="{{ $value }}" @selected(($member?->role ?? 'member') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="field" id="title-field-{{ $sfx }}" @if (($member?->role ?? 'member') !== 'other') hidden @endif><label for="title-{{ $sfx }}">Role name for “Other”</label><input id="title-{{ $sfx }}" name="title" value="{{ $member?->title }}" maxlength="80" placeholder="e.g. Imam, Muazzin, Advisor"></div>
    <div class="field"><label for="phone-{{ $sfx }}">Phone</label><input id="phone-{{ $sfx }}" name="phone" value="{{ $member?->phone }}" maxlength="32" inputmode="tel"></div>
    <div class="field"><label for="order-{{ $sfx }}">Order within role</label><input id="order-{{ $sfx }}" name="sort_order" type="number" min="0" max="999" value="{{ $member?->sort_order }}" placeholder="last"></div>
    <div class="field"><label for="photo-{{ $sfx }}">Photo (optional, up to 2 MB)</label><input id="photo-{{ $sfx }}" name="photo" type="file" accept="image/jpeg,image/png,image/webp"></div>
    <label class="check"><input type="checkbox" name="show_phone" value="1" @checked($member?->show_phone)> Show phone on the public site</label>
    <label class="check"><input type="checkbox" name="is_active" value="1" @checked($member?->is_active ?? true)> Show</label>
    @if ($member?->photo_path)
        <label class="check"><input type="checkbox" name="remove_photo" value="1"> Remove photo</label>
    @endif
    <div class="field full"><button class="btn secondary" type="submit">{{ $member ? 'Save' : 'Add member' }}</button></div>
</form>
