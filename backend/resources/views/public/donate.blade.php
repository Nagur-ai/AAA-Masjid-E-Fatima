@extends('layouts.public')

@section('title', 'Donate · '.$masjid->name)

@section('content')
    <div class="page-head"><h1>Donate to {{ $masjid->name }}</h1></div>

    @if ($pledge = session('pledge'))
        <div class="card" style="margin-bottom:16px">
            <h2>JazakAllahu khayran</h2>
            <p>Your pledge of <strong>{{ $pledge['currency'] }} {{ number_format($pledge['amount'], 2) }}</strong> to the
                <strong>{{ $pledge['fund'] }}</strong> fund is recorded as reference <strong>#{{ $pledge['id'] }}</strong>.
                The treasurer will issue your receipt once the payment is received.</p>
            @if ($masjid->bank_details)
                <p class="muted">Please transfer using these details and quote reference #{{ $pledge['id'] }}:</p>
                <pre style="white-space:pre-wrap;margin:0">{{ $masjid->bank_details }}</pre>
            @endif
        </div>
    @endif

    <div class="grid grid-2">
        <form method="POST" action="{{ route('masjid.donate', $masjid) }}" class="card">
            @csrf
            <div class="form-grid">
                <div class="field full">
                    <label for="fund_id">Fund</label>
                    <select id="fund_id" name="fund_id" required>
                        @foreach ($funds as $fund)
                            <option value="{{ $fund->id }}" @selected(old('fund_id') == $fund->id)>{{ $fund->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="amount">Amount ({{ $masjid->currency }})</label>
                    <input id="amount" name="amount" type="number" min="1" step="0.01" inputmode="decimal" value="{{ old('amount') }}" required>
                </div>
                <div class="field">
                    <label for="payment_method">Paying by</label>
                    <select id="payment_method" name="payment_method">
                        @foreach (\App\Enums\PaymentMethod::cases() as $m)
                            @continue($m === \App\Enums\PaymentMethod::Cash)
                            <option value="{{ $m->value }}" @selected(old('payment_method', 'bank_transfer') === $m->value)>{{ $m->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="donor_name">Your name</label>
                    <input id="donor_name" name="donor_name" value="{{ old('donor_name') }}" autocomplete="name">
                </div>
                <div class="field">
                    <label for="donor_phone">Phone</label>
                    <input id="donor_phone" name="donor_phone" value="{{ old('donor_phone') }}" autocomplete="tel">
                </div>
                <div class="field full">
                    <label for="donor_email">Email (for your receipt)</label>
                    <input id="donor_email" name="donor_email" type="email" value="{{ old('donor_email') }}" autocomplete="email">
                </div>
                <div class="field full">
                    <label for="payment_reference">Transfer / UPI reference (if already paid)</label>
                    <input id="payment_reference" name="payment_reference" value="{{ old('payment_reference') }}">
                </div>
                <label class="check full"><input type="checkbox" name="is_anonymous" value="1" @checked(old('is_anonymous'))> Keep my name private</label>
                <div class="field full"><button class="btn" type="submit">Pledge donation</button></div>
            </div>
        </form>

        <div class="card">
            <h2>Funds</h2>
            @foreach ($funds as $fund)
                <p><strong>{{ $fund->name }}</strong> <span class="badge">{{ $fund->type->label() }}</span><br>
                    <span class="muted">{{ $fund->description }}</span></p>
            @endforeach
            @if ($masjid->bank_details)
                <h3>Bank details</h3>
                <pre style="white-space:pre-wrap;margin:0">{{ $masjid->bank_details }}</pre>
            @endif
        </div>
    </div>
@endsection
