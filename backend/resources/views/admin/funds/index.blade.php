@extends('layouts.admin')

@section('title', 'Funds')

@section('content')
    <div class="page-head"><h1>Funds &amp; categories</h1></div>
    <p class="muted">Restricted funds (such as Zakat) can only be spent up to their balance.</p>

    <div class="table-wrap" style="margin-bottom:16px">
        <table>
            <thead><tr><th>Fund</th><th>Type</th><th class="right">Balance</th><th class="right">Target</th><th>Flags</th><th></th></tr></thead>
            <tbody>
            @foreach ($funds as $fund)
                <tr>
                    <td>{{ $fund->name }}<br><small>{{ $fund->description }}</small></td>
                    <td>{{ $fund->type->label() }}</td>
                    <td class="right">{{ number_format($balances[$fund->id] ?? 0, 2) }}</td>
                    <td class="right">{{ $fund->target_amount ? number_format($fund->target_amount, 2) : '—' }}</td>
                    <td>
                        @if($fund->is_restricted)<span class="badge">restricted</span>@endif
                        <span class="badge {{ $fund->is_active ? 'completed' : 'cancelled' }}">{{ $fund->is_active ? 'active' : 'inactive' }}</span>
                    </td>
                    <td>
                        <details>
                            <summary>Edit</summary>
                            @include('admin.funds._form', ['fund' => $fund, 'action' => route('admin.funds.update', $fund), 'method' => 'PUT'])
                            <form method="POST" action="{{ route('admin.funds.destroy', $fund) }}" style="margin-top:8px" onsubmit="return confirm('Delete {{ $fund->name }}?')">
                                @csrf @method('DELETE')
                                <button class="btn small danger" type="submit">Delete fund</button>
                            </form>
                        </details>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div class="grid grid-2">
        <div class="card">
            <h2>New fund</h2>
            @include('admin.funds._form', ['fund' => null, 'action' => route('admin.funds.store'), 'method' => 'POST'])
        </div>
        <div class="card">
            <h2>Ledger categories</h2>
            <div class="table-wrap" style="margin-bottom:12px">
                <table>
                    <thead><tr><th>Category</th><th>Type</th><th></th></tr></thead>
                    <tbody>
                    @foreach ($categories as $c)
                        <tr>
                            <td>
                                @if ($c->is_system)
                                    {{ $c->name }} <span class="badge">system</span>
                                @else
                                    <form method="POST" action="{{ route('admin.categories.update', $c) }}" class="actions" style="align-items:center">
                                        @csrf @method('PUT')
                                        <input name="name" value="{{ $c->name }}" maxlength="80" required style="width:auto;flex:1;min-width:140px" aria-label="Category name">
                                        <button class="btn small secondary" type="submit">Rename</button>
                                    </form>
                                @endif
                            </td>
                            <td><span class="badge {{ $c->type->value }}">{{ $c->type->value }}</span></td>
                            <td>
                                @unless ($c->is_system)
                                    <form method="POST" action="{{ route('admin.categories.destroy', $c) }}" class="inline" onsubmit="return confirm('Delete {{ $c->name }}?')">
                                        @csrf @method('DELETE')
                                        <button class="btn small danger" type="submit">Delete</button>
                                    </form>
                                @endunless
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <form method="POST" action="{{ route('admin.categories.store') }}" class="form-grid">
                @csrf
                <div class="field"><label for="cat_name">Name</label><input id="cat_name" name="name" required></div>
                <div class="field"><label for="cat_type">Type</label>
                    <select id="cat_type" name="type"><option value="expense">Expense</option><option value="income">Income</option></select>
                </div>
                <div class="field full"><button class="btn secondary" type="submit">Add category</button></div>
            </form>
        </div>
    </div>
@endsection
