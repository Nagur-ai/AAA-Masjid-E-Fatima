@extends('layouts.admin')

@section('title', 'Ledger')

@section('content')
    <div class="page-head">
        <h1>Ledger</h1>
        <div class="actions">
            <a class="btn" href="{{ route('admin.transactions.create', ['type' => 'expense']) }}">Record expense</a>
            <a class="btn secondary" href="{{ route('admin.transactions.create', ['type' => 'income']) }}">Record other income</a>
        </div>
    </div>

    <form class="filters" method="GET">
        <div class="field"><label for="q">Search</label><input id="q" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Ref, description, party"></div>
        <div class="field"><label for="type">Type</label>
            <select id="type" name="type">
                <option value="">All</option>
                <option value="income" @selected(($filters['type'] ?? '') === 'income')>Income</option>
                <option value="expense" @selected(($filters['type'] ?? '') === 'expense')>Expense</option>
            </select>
        </div>
        <div class="field"><label for="category_id">Category</label>
            <select id="category_id" name="category_id">
                <option value="">All</option>
                @foreach ($categories as $c)
                    <option value="{{ $c->id }}" @selected(($filters['category_id'] ?? '') == $c->id)>{{ $c->name }} ({{ $c->type->value }})</option>
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

    <div class="table-wrap">
        <table>
            <thead>
            <tr><th>Ref</th><th>Date</th><th>Type</th><th>Category</th><th>Fund</th><th class="right">Amount</th><th>Party / description</th><th>By</th><th></th></tr>
            </thead>
            <tbody>
            @forelse ($transactions as $t)
                <tr @class(['voided' => $t->isVoided()])>
                    <td>{{ $t->reference_no }}</td>
                    <td>{{ $t->transacted_on->format('j M Y') }}</td>
                    <td><span class="badge {{ $t->type->value }}">{{ $t->type->value }}</span></td>
                    <td>{{ $t->category->name }}</td>
                    <td>{{ $t->fund?->name ?? '—' }}</td>
                    <td class="right">{{ $t->type === \App\Enums\TransactionType::Expense ? '−' : '' }}{{ number_format($t->amount, 2) }}</td>
                    <td class="wrap">
                        @if($t->party_name)<strong>{{ $t->party_name }}</strong><br>@endif
                        {{ $t->description }}
                        @if($t->isVoided())<br><small>Voided: {{ $t->void_reason }}</small>@endif
                    </td>
                    <td>{{ $t->recorder->name }}</td>
                    <td>
                        <div class="actions">
                            @if ($t->attachment_path)
                                <a class="btn small secondary" href="{{ route('admin.transactions.attachment', $t) }}">File</a>
                            @endif
                            @if (! $t->isVoided() && $t->source_type === null)
                                <details>
                                    <summary>Void</summary>
                                    <form method="POST" action="{{ route('admin.transactions.void', $t) }}">
                                        @csrf
                                        <input name="reason" placeholder="Reason" required>
                                        <button class="btn small danger" type="submit">Void</button>
                                    </form>
                                </details>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="muted">No transactions found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $transactions->links() }}
@endsection
