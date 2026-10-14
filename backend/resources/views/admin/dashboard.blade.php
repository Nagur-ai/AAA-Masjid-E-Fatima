@extends('layouts.admin')

@section('title', 'Dashboard')

@use('App\Enums\Role')
@use('App\Services\Finance\Money')
@php
    $role = auth()->user()->role;
    $has = fn (string $panel) => in_array($panel, $panels, true);
@endphp

@section('content')
    <div class="page-head">
        <div>
            <h1>Assalamu alaikum, {{ auth()->user()->name }}</h1>
            <span class="muted">{{ $today->format('l') }} (<span lang="te">{{ $today->locale('te')->dayName }}</span>) {{ $today->format('j F Y') }} &middot; {{ $hijri->format() }}
                &middot; <span class="badge {{ $role === Role::Admin ? 'confirmed' : 'pending' }}">{{ $role->label() }}</span></span>
        </div>
        <div class="actions">
            <a class="btn" href="{{ route('admin.donations.create') }}">Record donation</a>
            <a class="btn secondary" href="{{ route('admin.transactions.create', ['type' => 'expense']) }}">Record expense</a>
        </div>
    </div>

    @if ($has(Role::PANEL_SUMMARY))
        @php($cur = $summary['currency'])
        <div class="grid grid-4" style="margin-bottom:16px">
            <div class="card stat"><div class="label">Income this month</div><div class="value">{{ Money::format($summary['income'], $cur) }}</div></div>
            <div class="card stat"><div class="label">Expenses this month</div><div class="value">{{ Money::format($summary['expense'], $cur) }}</div></div>
            <div class="card stat"><div class="label">Net</div><div class="value">{{ Money::format($summary['net'], $cur) }}</div></div>
            <div class="card stat"><div class="label">Donations ({{ $summary['donations']['count'] }})</div><div class="value">{{ Money::format($summary['donations']['total'], $cur) }}</div></div>
        </div>
    @endif

    @if ($has(Role::PANEL_PENDING))
        <div class="card" style="margin-bottom:16px">
            <div class="page-head" style="margin-bottom:8px">
                <h2 style="margin:0">Pledges waiting for payment ({{ $pendingCount }})</h2>
                @if ($pendingCount > $pendingDonations->count())
                    <a href="{{ route('admin.donations.index', ['status' => 'pending']) }}">View all</a>
                @endif
            </div>
            @if ($pendingDonations->isEmpty())
                <p class="muted" style="margin:0">No pledges waiting. New pledges from the website and app appear here.</p>
            @else
                @include('admin.donations._table', ['donations' => $pendingDonations])
            @endif
        </div>
    @endif

    <div class="grid grid-2">
        @if ($has(Role::PANEL_FUNDS))
            <div class="card">
                <h2>Fund balances</h2>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Fund</th><th class="right">Balance</th><th>Target</th></tr></thead>
                        <tbody>
                        @foreach ($summary['funds'] as $f)
                            <tr>
                                <td>{{ $f['name'] }} @if($f['is_restricted'])<span class="badge">restricted</span>@endif</td>
                                <td class="right">{{ number_format($f['balance'], 2) }}</td>
                                <td style="min-width:120px">
                                    @if ($f['target_amount'] > 0)
                                        <div class="bar"><span style="width:{{ min(100, max(0, $f['balance'] / $f['target_amount'] * 100)) }}%"></span></div>
                                        <small>{{ number_format($f['target_amount'], 0) }}</small>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if ($has(Role::PANEL_EXPENSES))
            <div class="card">
                <div class="page-head" style="margin-bottom:8px">
                    <h2 style="margin:0">Latest expenses</h2>
                    <a href="{{ route('admin.transactions.index', ['type' => 'expense']) }}">Ledger</a>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Date</th><th>Category</th><th>Fund</th><th class="right">Amount</th></tr></thead>
                        <tbody>
                        @forelse ($recentExpenses as $t)
                            <tr title="{{ $t->description }}">
                                <td>{{ $t->transacted_on->format('j M') }}</td>
                                <td>{{ $t->category->name }}</td>
                                <td>{{ $t->fund?->name ?? '—' }}</td>
                                <td class="right">{{ number_format($t->amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="muted">No expenses recorded yet.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if ($has(Role::PANEL_PRAYERS))
            <div class="card">
                <div class="page-head" style="margin-bottom:8px">
                    <h2 style="margin:0">Today's prayers</h2>
                    <a class="btn small secondary" href="{{ route('admin.prayer-times.edit') }}">Edit times</a>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Prayer</th><th>Adhan</th><th>Iqamah</th></tr></thead>
                        <tbody>
                        @foreach (\App\Enums\Prayer::cases() as $p)
                            @php($isNext = $nextPrayer && $nextPrayer['prayer'] === $p->value)
                            <tr @class(['next-row' => $isNext])>
                                <td>{{ $p->label() }} @if (in_array($p->value, $editedPrayers, true))<span class="badge pending">edited</span>@endif @if ($isNext)<br><span class="next-tag">Next</span><br><span class="next-in">{{ $nextPrayer['at']->isSameDay($today) ? '' : 'tomorrow, ' }}in {{ $today->diff($nextPrayer['at'])->format('%hh %Im') }}</span>@endif</td>
                                <td>{{ $schedule->times[$p->value]?->format('H:i') }}</td>
                                <td>{{ $schedule->iqamah[$p->value]?->format('H:i') ?? '—' }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if ($has(Role::PANEL_STAFF))
            <div class="card">
                <div class="page-head" style="margin-bottom:8px">
                    <h2 style="margin:0">Staff</h2>
                    <a href="{{ route('admin.users.index') }}">Manage</a>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Name</th><th>Email</th><th>Role</th></tr></thead>
                        <tbody>
                        @foreach ($staff as $u)
                            <tr><td>{{ $u->name }}</td><td>{{ $u->email }}</td><td><span class="badge">{{ $u->role->label() }}</span></td></tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

    @if ($has(Role::PANEL_RAMADAN))
        @php($todayStr = $today->toDateString())
        @php($ramadanToday = collect($ramadan['days'])->firstWhere('date', $todayStr))
        @php($date = fn (string $d, string $f) => \Carbon\Carbon::parse($d)->format($f))
        <div class="card" style="margin-top:16px">
            @include('partials.ramadan-image', ['url' => $ramadan['timetable_image_url']])
            <div class="page-head" style="margin-bottom:8px">
                <div>
                    <h2 style="margin:0">Ramadan {{ $ramadan['hijri_year'] }} <span lang="te" class="muted">· రమజాన్ క్యాలెండర్</span></h2>
                    <span class="muted">
                        {{ $date($ramadan['starts_on'], 'D j M') }} – {{ $date($ramadan['ends_on'], 'D j M Y') }}
                        &middot; {{ $ramadan['total_days'] }} days
                        &middot; Eid al-Fitr {{ $date($ramadan['eid_al_fitr'], 'D j M') }}
                        @if ($ramadan['taraweeh_time']) &middot; Taraweeh {{ $ramadan['taraweeh_time'] }} @endif
                    </span>
                </div>
                <div class="actions">
                    <span class="badge {{ $ramadan['is_confirmed'] ? 'confirmed' : 'estimated' }}">{{ $ramadan['is_confirmed'] ? 'confirmed' : 'estimated' }}</span>
                    <a class="btn secondary" href="{{ route('admin.ramadan.edit', $ramadan['hijri_year']) }}">Manage Ramadan</a>
                </div>
            </div>
            @unless ($ramadan['is_confirmed'])
                <div class="alert warn">These dates are estimated. Confirm the start date after the moon sighting so the community sees the right timetable.</div>
            @endunless

            @if ($ramadanToday)
                <div class="grid grid-3" style="margin-bottom:16px">
                    <div class="card stat"><div class="label">Today · Roza {{ $ramadanToday['day'] }}</div><div class="value" lang="te">{{ $ramadanToday['weekday_te'] }}</div></div>
                    <div class="card stat"><div class="label">Sehri ends · <span lang="te">సహరీ</span></div><div class="value">{{ $ramadanToday['sehri_ends'] }}</div></div>
                    <div class="card stat"><div class="label">Iftar · <span lang="te">ఇఫ్తార్</span></div><div class="value">{{ $ramadanToday['iftar'] }}</div></div>
                </div>
            @endif

            <h3 lang="te" style="margin-top:0">సహరీ & ఇఫ్తార్ దుఆలు</h3>
            @include('partials.ramadan-duas', ['duas' => $ramadan['duas']])

            <div style="margin-top:16px">
                @include('partials.ramadan-table', ['days' => $ramadan['days'], 'todayStr' => $todayStr, 'title' => $currentMasjid->name])
            </div>
        </div>
    @endif

    @if ($has(Role::PANEL_DONATIONS))
        <div class="card" style="margin-top:16px">
            <div class="page-head" style="margin-bottom:8px">
                <h2 style="margin:0">Latest donations</h2>
                <a href="{{ route('admin.donations.index') }}">All donations</a>
            </div>
            @include('admin.donations._table', ['donations' => $recentDonations])
        </div>
    @endif
@endsection
