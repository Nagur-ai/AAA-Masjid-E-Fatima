@extends('layouts.admin')

@section('title', 'Staff')

@section('content')
    <div class="page-head"><h1>Staff</h1></div>
    <p class="muted">Admins manage everything; treasurers manage donations, the ledger and reports. Set a person to “member” to revoke access.</p>

    <div class="table-wrap" style="margin-bottom:16px">
        <table>
            <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th></th></tr></thead>
            <tbody>
            @foreach ($users as $u)
                <tr>
                    <td>{{ $u->name }}</td>
                    <td>{{ $u->email }}</td>
                    <td>{{ $u->phone }}</td>
                    <td>
                        <form method="POST" action="{{ route('admin.users.update', $u) }}" class="actions">
                            @csrf @method('PUT')
                            <select name="role" style="width:auto">
                                @foreach (\App\Enums\Role::cases() as $r)
                                    <option value="{{ $r->value }}" @selected($u->role === $r)>{{ ucfirst($r->value) }}</option>
                                @endforeach
                            </select>
                            <button class="btn small secondary" type="submit">Update</button>
                        </form>
                    </td>
                    <td>
                        @unless ($u->is(auth()->user()))
                            <form method="POST" action="{{ route('admin.users.destroy', $u) }}" class="inline" onsubmit="return confirm('Delete {{ $u->name }}?')">
                                @csrf @method('DELETE')
                                <button class="btn small danger" type="submit">Delete</button>
                            </form>
                        @endunless
                    </td>
                </tr>
                <tr class="edit-row">
                    <td colspan="5">
                        <details>
                            <summary>Edit {{ $u->name }}</summary>
                            <form method="POST" action="{{ route('admin.users.update', $u) }}" class="form-grid" style="margin-top:8px">
                                @csrf @method('PUT')
                                <div class="field"><label for="name-{{ $u->id }}">Name</label><input id="name-{{ $u->id }}" name="name" value="{{ $u->name }}" required></div>
                                <div class="field"><label for="email-{{ $u->id }}">Email</label><input id="email-{{ $u->id }}" name="email" type="email" value="{{ $u->email }}" required></div>
                                <div class="field"><label for="phone-{{ $u->id }}">Phone</label><input id="phone-{{ $u->id }}" name="phone" value="{{ $u->phone }}"></div>
                                <div class="field"><label for="role-{{ $u->id }}">Role</label>
                                    <select id="role-{{ $u->id }}" name="role">
                                        @foreach (\App\Enums\Role::cases() as $r)<option value="{{ $r->value }}" @selected($u->role === $r)>{{ ucfirst($r->value) }}</option>@endforeach
                                    </select>
                                </div>
                                <div class="field"><label for="password-{{ $u->id }}">New password (optional)</label><input id="password-{{ $u->id }}" name="password" type="password" autocomplete="new-password"></div>
                                <div class="field full"><button class="btn secondary" type="submit">Save</button></div>
                            </form>
                        </details>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <form method="POST" action="{{ route('admin.users.store') }}" class="card">
        @csrf
        <h2>Add staff member</h2>
        <div class="form-grid">
            <div class="field"><label for="name">Name</label><input id="name" name="name" value="{{ old('name') }}" required></div>
            <div class="field"><label for="email">Email</label><input id="email" name="email" type="email" value="{{ old('email') }}" required></div>
            <div class="field"><label for="phone">Phone</label><input id="phone" name="phone" value="{{ old('phone') }}"></div>
            <div class="field"><label for="role">Role</label>
                <select id="role" name="role">@foreach ($roles as $r)<option value="{{ $r->value }}">{{ ucfirst($r->value) }}</option>@endforeach</select>
            </div>
            <div class="field"><label for="password">Initial password</label><input id="password" name="password" type="password" required autocomplete="new-password"></div>
            <div class="field full"><button class="btn" type="submit">Add</button></div>
        </div>
    </form>
@endsection
