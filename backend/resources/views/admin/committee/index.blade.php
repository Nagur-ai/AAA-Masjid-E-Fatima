@extends('layouts.admin')

@section('title', 'Committee')

@section('content')
    <div class="page-head">
        <div>
            <h1>Masjid committee</h1>
            <span class="muted">Shown on the public site under “Committee” in this order: by role (Mutawalli, President, Vice President, Secretary, Joint Secretary, Treasurer, Member, Other), then by the “Order” number. Untick “Show” to hide someone without deleting them.</span>
        </div>
        <div class="actions">
            <a class="btn secondary" href="{{ route('masjid.committee', $currentMasjid) }}">View public page</a>
        </div>
    </div>

    <div class="table-wrap committee-table" style="margin-bottom:16px;max-width:none">
        <table>
            <thead><tr><th class="num">No.</th><th class="photo">Photo</th><th>Name</th><th>Role</th><th>Phone</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse ($members as $m)
                <tr @class(['bearer' => in_array($m->role, \App\Models\CommitteeMember::OFFICE_BEARERS, true), 'role-'.$m->role, 'dua-hidden' => ! $m->is_active])>
                    <td class="num">{{ $loop->iteration }}</td>
                    <td class="photo">@if ($m->photo_path)<img class="avatar" src="{{ route('masjid.committee.photo', [$currentMasjid, $m]) }}" alt="">@else<span class="muted">—</span>@endif</td>
                    <td class="name">{{ $m->name }}</td>
                    <td>
                        <span class="role-badge">{{ $m->role === 'other' && $m->title ? $m->title : $roles[$m->role] }}</span>
                    </td>
                    <td>{{ $m->phone ?: '—' }}@if ($m->phone)<br><small class="muted">{{ $m->show_phone ? 'shown publicly' : 'not shown' }}</small>@endif</td>
                    <td><span class="badge {{ $m->is_active ? 'completed' : 'cancelled' }}">{{ $m->is_active ? 'shown' : 'hidden' }}</span></td>
                    <td>
                        <form method="POST" action="{{ route('admin.committee.destroy', $m) }}" class="inline" onsubmit="return confirm('Remove {{ $m->name }} from the committee?')">
                            @csrf @method('DELETE')
                            <button class="btn small danger" type="submit">Delete</button>
                        </form>
                    </td>
                </tr>
                <tr class="edit-row">
                    <td colspan="7">
                        <details>
                            <summary>Edit {{ $m->name }}</summary>
                            @include('admin.committee._form', ['member' => $m, 'action' => route('admin.committee.update', $m), 'method' => 'PUT'])
                        </details>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="muted">No committee members yet. Add the mutawalli, president, secretary and others below.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="card">
        <h2>Add a member</h2>
        @include('admin.committee._form', ['member' => null, 'action' => route('admin.committee.store'), 'method' => 'POST'])
    </div>
@endsection
