{{-- Donation receipt body, shared by the admin panel and the member's account. --}}
<div class="page-head">
    <div>
        <h1>{{ $masjid->name }}</h1>
        <span class="muted">{{ $masjid->address }}{{ $masjid->city ? ', '.$masjid->city : '' }}</span>
    </div>
    <span class="badge {{ $donation->status->value }}">{{ $donation->status->value }}</span>
</div>
<h2>Donation receipt</h2>
<dl>
    <dt>Receipt no.</dt><dd>{{ $donation->receipt_no }}</dd>
    <dt>Date</dt><dd>{{ $donation->donated_at->timezone($masjid->timezone)->format('j F Y, H:i') }}</dd>
    <dt>Received from</dt><dd>{{ $donation->displayName() }}</dd>
    <dt>Fund</dt><dd>{{ $donation->fund->name }}</dd>
    <dt>Amount</dt><dd>{{ \App\Services\Finance\Money::format($donation->amount, $donation->currency) }}</dd>
    <dt>Method</dt><dd>{{ $donation->payment_method->label() }}{{ $donation->payment_reference ? ' · '.$donation->payment_reference : '' }}</dd>
    <dt>Received by</dt><dd>{{ $donation->recorder?->name ?? '—' }}</dd>
</dl>
<p style="margin-top:16px" class="muted">May Allah accept your charity and reward you. JazakAllahu khayran.</p>
