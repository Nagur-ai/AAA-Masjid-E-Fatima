@extends('layouts.public')

@section('title', 'My donations')

@section('content')
    <div class="page-head">
        <div>
            <h1>Assalamu alaikum, {{ auth()->user()->name }}</h1>
            <span class="muted">Your donations and receipts</span>
        </div>
        @if ($donateTo)
            <a class="btn" href="{{ route('masjid.donate', $donateTo) }}">Donate to {{ $donateTo->name }}</a>
        @endif
    </div>

    <div class="grid grid-3" style="margin-bottom:16px">
        @forelse ($totals as $t)
            <div class="card stat"><div class="label">Given ({{ $t->count }} {{ \Illuminate\Support\Str::plural('donation', $t->count) }})</div>
                <div class="value">{{ \App\Services\Finance\Money::format($t->total, $t->currency) }}</div></div>
        @empty
            <div class="card stat"><div class="label">Given</div><div class="value">—</div></div>
        @endforelse
        <div class="card stat"><div class="label">Waiting for confirmation</div><div class="value">{{ $pendingCount }}</div></div>
    </div>

    @if ($pendingCount)
        <div class="alert warn">Pledges stay <strong>pending</strong> until the masjid treasurer confirms your payment. You get a receipt number then.</div>
    @endif

    <div class="table-wrap">
        <table>
            <thead><tr><th>Date</th><th>Masjid</th><th>Fund</th><th class="right">Amount</th><th>Method</th><th>Status</th><th>Receipt</th></tr></thead>
            <tbody>
            @forelse ($donations as $d)
                <tr>
                    <td>{{ ($d->donated_at ?? $d->created_at)->timezone($d->masjid->timezone)->format('j M Y') }}</td>
                    <td><a href="{{ route('masjid.show', $d->masjid) }}">{{ $d->masjid->name }}</a></td>
                    <td>{{ $d->fund->name }}</td>
                    <td class="right">{{ $d->currency }} {{ number_format($d->amount, 2) }}</td>
                    <td>{{ $d->payment_method->label() }}</td>
                    <td><span class="badge {{ $d->status->value }}">{{ $d->status->value }}</span></td>
                    <td>
                        @if ($d->receipt_no)
                            <a href="{{ route('account.receipt', $d) }}">{{ $d->receipt_no }}</a>
                        @else
                            <span class="muted">Ref #{{ $d->id }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="muted">You have not donated while signed in yet. Donations you make while signed in, here or in the mobile app, appear in this list.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $donations->links() }}
@endsection
