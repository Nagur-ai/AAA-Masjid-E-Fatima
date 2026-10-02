@extends('layouts.admin')

@section('title', 'Record donation')

@section('content')
    <div class="page-head"><h1>Record a received donation</h1></div>
    <p class="muted">Use this for cash, cheques and transfers already received. A receipt number is issued and the income is posted to the ledger immediately.</p>

    <form method="POST" action="{{ route('admin.donations.store') }}" class="card">
        @csrf
        <div class="form-grid">
            <div class="field">
                <label for="fund_id">Fund</label>
                <select id="fund_id" name="fund_id" required>
                    @foreach ($funds as $fund)
                        <option value="{{ $fund->id }}" @selected(old('fund_id') == $fund->id)>{{ $fund->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="amount">Amount ({{ $currentMasjid->currency }})</label>
                <input id="amount" name="amount" type="number" min="0.01" step="0.01" value="{{ old('amount') }}" required>
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
                <label for="payment_reference">Reference / cheque no.</label>
                <input id="payment_reference" name="payment_reference" value="{{ old('payment_reference') }}">
            </div>
            <div class="field">
                <label for="donor_name">Donor name</label>
                <input id="donor_name" name="donor_name" value="{{ old('donor_name') }}">
            </div>
            <div class="field">
                <label for="donor_phone">Donor phone</label>
                <input id="donor_phone" name="donor_phone" value="{{ old('donor_phone') }}">
            </div>
            <div class="field">
                <label for="donor_email">Donor email</label>
                <input id="donor_email" name="donor_email" type="email" value="{{ old('donor_email') }}">
            </div>
            <div class="field">
                <label for="donated_at">Received at</label>
                <input id="donated_at" name="donated_at" type="datetime-local" value="{{ old('donated_at') }}">
            </div>
            <div class="field full">
                <label for="notes">Notes</label>
                <textarea id="notes" name="notes" rows="2">{{ old('notes') }}</textarea>
            </div>
            <label class="check full"><input type="checkbox" name="is_anonymous" value="1" @checked(old('is_anonymous'))> Anonymous donor</label>
            <div class="field full"><button class="btn" type="submit">Save and issue receipt</button></div>
        </div>
    </form>
@endsection
