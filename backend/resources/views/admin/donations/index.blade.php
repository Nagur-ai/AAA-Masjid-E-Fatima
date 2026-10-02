@extends('layouts.admin')

@section('title', 'Donations')

@section('content')
    <div class="page-head">
        <h1>Donations</h1>
        <a class="btn" href="{{ route('admin.donations.create') }}">Record donation</a>
    </div>

    <form class="filters" method="GET">
        <div class="field"><label for="q">Search</label><input id="q" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Receipt, name, phone, ref"></div>
        <div class="field"><label for="status">Status</label>
            <select id="status" name="status">
                <option value="">All</option>
                @foreach (\App\Enums\DonationStatus::cases() as $s)
                    <option value="{{ $s->value }}" @selected(($filters['status'] ?? '') === $s->value)>{{ ucfirst($s->value) }}</option>
                @endforeach
            </select>
        </div>
        <div class="field"><label for="fund_id">Fund</label>
            <select id="fund_id" name="fund_id">
                <option value="">All</option>
                @foreach ($funds as $f)
                    <option value="{{ $f->id }}" @selected(($filters['fund_id'] ?? '') == $f->id)>{{ $f->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="field"><label for="from">From</label><input id="from" type="date" name="from" value="{{ $filters['from'] ?? '' }}"></div>
        <div class="field"><label for="to">To</label><input id="to" type="date" name="to" value="{{ $filters['to'] ?? '' }}"></div>
        <button class="btn secondary" type="submit">Filter</button>
    </form>

    @include('admin.donations._table')
    {{ $donations->links() }}
@endsection
