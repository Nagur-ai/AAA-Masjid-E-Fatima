@extends('layouts.admin')

@section('title', 'Record '.$type->value)

@section('content')
    <div class="page-head"><h1>Record {{ $type === \App\Enums\TransactionType::Expense ? 'an expense' : 'other income' }}</h1></div>
    @if ($type === \App\Enums\TransactionType::Income)
        <p class="muted">For donations use <a href="{{ route('admin.donations.create') }}">Record donation</a> so the donor gets a receipt.</p>
    @endif

    <form method="POST" action="{{ route('admin.transactions.store') }}" enctype="multipart/form-data" class="card">
        @csrf
        <input type="hidden" name="type" value="{{ $type->value }}">
        <div class="form-grid">
            <div class="field">
                <label for="category_id">Category</label>
                <select id="category_id" name="category_id" required>
                    @foreach ($categories->where('type', $type) as $c)
                        <option value="{{ $c->id }}" @selected(old('category_id') == $c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="fund_id">{{ $type === \App\Enums\TransactionType::Expense ? 'Paid from fund' : 'Into fund' }}</label>
                <select id="fund_id" name="fund_id">
                    <option value="">— none —</option>
                    @foreach ($funds as $f)
                        <option value="{{ $f->id }}" @selected(old('fund_id') == $f->id)>
                            {{ $f->name }} ({{ number_format($balances[$f->id] ?? 0, 2) }}){{ $f->is_restricted ? ' · restricted' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="amount">Amount ({{ $currentMasjid->currency }})</label>
                <input id="amount" name="amount" type="number" min="0.01" step="0.01" value="{{ old('amount') }}" required>
            </div>
            <div class="field">
                <label for="transacted_on">Date</label>
                <input id="transacted_on" name="transacted_on" type="date" value="{{ old('transacted_on', now($currentMasjid->timezone)->toDateString()) }}" required>
            </div>
            <div class="field">
                <label for="payment_method">Method</label>
                <select id="payment_method" name="payment_method">
                    @foreach ($methods as $m)
                        <option value="{{ $m->value }}" @selected(old('payment_method', 'cash') === $m->value)>{{ $m->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="party_name">{{ $type === \App\Enums\TransactionType::Expense ? 'Paid to' : 'Received from' }}</label>
                <input id="party_name" name="party_name" value="{{ old('party_name') }}">
            </div>
            <div class="field full">
                <label for="description">Description</label>
                <input id="description" name="description" value="{{ old('description') }}" required maxlength="255">
            </div>
            <div class="field full">
                <label for="attachment">Bill / invoice (PDF or image, max 5 MB)</label>
                <input id="attachment" name="attachment" type="file" accept=".pdf,.jpg,.jpeg,.png">
            </div>
            <div class="field full"><button class="btn" type="submit">Post to ledger</button></div>
        </div>
    </form>
@endsection
