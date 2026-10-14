@extends('layouts.public')

@section('title', __('Masjid committee').' · '.$masjid->name)

@section('content')
    <div class="page-head"><h1>{{ __('Masjid committee') }}</h1></div>

    @if ($members->isEmpty())
        <div class="card"><p class="muted" style="margin:0">{{ __('The committee has not been announced yet.') }}</p></div>
    @else
        @php($withPhotos = $members->contains(fn ($m) => $m->photo_path))
        <div class="table-wrap committee-table">
            <table>
                <thead>
                <tr>
                    <th class="num">{{ __('No.') }}</th>
                    @if ($withPhotos)<th class="photo"><span class="sr-only">{{ __('Photo') }}</span></th>@endif
                    <th>{{ __('Name') }}</th>
                    <th>{{ __('Role') }}</th>
                    <th>{{ __('Phone') }}</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($members as $m)
                    <tr @class(['bearer' => in_array($m->role, \App\Models\CommitteeMember::OFFICE_BEARERS, true), 'role-'.$m->role])>
                        <td class="num">{{ $loop->iteration }}</td>
                        @if ($withPhotos)
                            <td class="photo">@if ($m->photo_path)<img class="avatar" src="{{ route('masjid.committee.photo', [$masjid, $m]) }}" alt="" loading="lazy">@endif</td>
                        @endif
                        <td class="name">{{ $m->name }}</td>
                        <td>
                            <span class="role-badge">{{ $m->roleLabel() }}</span>
                        </td>
                        <td>
                            @if ($m->show_phone && $m->phone)
                                <a href="tel:{{ preg_replace('/[^0-9+]/', '', $m->phone) }}" dir="ltr">{{ $m->phone }}</a>
                            @else
                                <span class="muted">—</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
