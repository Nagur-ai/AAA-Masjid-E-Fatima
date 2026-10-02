@extends('layouts.admin')

@section('title', 'Reports')

@section('content')
    @php($cur = $report['currency'])
    <div class="page-head">
        <h1>Financial report</h1>
        <form class="filters" method="GET" style="margin:0">
            <div class="field"><label for="from">From</label><input id="from" type="date" name="from" value="{{ $report['from'] }}"></div>
            <div class="field"><label for="to">To</label><input id="to" type="date" name="to" value="{{ $report['to'] }}"></div>
            <button class="btn secondary" type="submit">Apply</button>
            <a class="btn secondary" href="{{ route('admin.reports.export', ['from' => $report['from'], 'to' => $report['to']]) }}">Export CSV</a>
            <button class="btn secondary" type="button" onclick="window.print()">Print</button>
        </form>
    </div>

    <div class="grid grid-4" style="margin-bottom:16px">
        <div class="card stat"><div class="label">Income</div><div class="value">{{ \App\Services\Finance\Money::format($report['income'], $cur) }}</div></div>
        <div class="card stat"><div class="label">Expenses</div><div class="value">{{ \App\Services\Finance\Money::format($report['expense'], $cur) }}</div></div>
        <div class="card stat"><div class="label">Net</div><div class="value">{{ \App\Services\Finance\Money::format($report['net'], $cur) }}</div></div>
        <div class="card stat"><div class="label">Donations ({{ $report['donations']['count'] }})</div><div class="value">{{ \App\Services\Finance\Money::format($report['donations']['total'], $cur) }}</div></div>
    </div>

    <div class="grid grid-2">
        <div class="card">
            <h2>By category</h2>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Category</th><th>Type</th><th class="right">Entries</th><th class="right">Total</th></tr></thead>
                    <tbody>
                    @forelse ($report['by_category'] as $row)
                        <tr><td>{{ $row['category'] }}</td><td><span class="badge {{ $row['type'] }}">{{ $row['type'] }}</span></td><td class="right">{{ $row['entries'] }}</td><td class="right">{{ number_format($row['total'], 2) }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="muted">No entries in this period.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card">
            <h2>Monthly</h2>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Month</th><th class="right">Income</th><th class="right">Expense</th><th class="right">Net</th></tr></thead>
                    <tbody>
                    @forelse ($report['monthly'] as $m)
                        <tr>
                            <td>{{ \Carbon\Carbon::createFromFormat('!Y-m', $m['month'])->format('M Y') }}</td>
                            <td class="right">{{ number_format($m['income'], 2) }}</td>
                            <td class="right">{{ number_format($m['expense'], 2) }}</td>
                            <td class="right">{{ number_format(bcsub($m['income'], $m['expense'], 2), 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="muted">No entries in this period.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card" style="margin-top:16px">
        <h2>Fund balances as of {{ \Carbon\Carbon::parse($report['to'])->format('j M Y') }}</h2>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Fund</th><th>Type</th><th class="right">Balance</th></tr></thead>
                <tbody>
                @foreach ($report['funds'] as $f)
                    <tr><td>{{ $f['name'] }} @if($f['is_restricted'])<span class="badge">restricted</span>@endif</td><td>{{ $f['type'] }}</td><td class="right">{{ number_format($f['balance'], 2) }}</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
