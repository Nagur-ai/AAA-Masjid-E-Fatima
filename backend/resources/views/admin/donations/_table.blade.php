<div class="table-wrap">
    <table>
        <thead>
        <tr><th>#</th><th>Receipt</th><th>Donor</th><th>Fund</th><th class="right">Amount</th><th>Method</th><th>Status</th><th>Date</th><th></th></tr>
        </thead>
        <tbody>
        @forelse ($donations as $d)
            <tr>
                <td>{{ $d->id }}</td>
                <td>{{ $d->receipt_no ?? '—' }}</td>
                <td>{{ $d->displayName() }}@if(! $d->is_anonymous && $d->donor_phone)<br><small>{{ $d->donor_phone }}</small>@endif</td>
                <td>{{ $d->fund->name }}</td>
                <td class="right">{{ number_format($d->amount, 2) }}</td>
                <td>{{ $d->payment_method->label() }}@if($d->payment_reference)<br><small>{{ $d->payment_reference }}</small>@endif</td>
                <td><span class="badge {{ $d->status->value }}">{{ $d->status->value }}</span></td>
                <td>{{ ($d->donated_at ?? $d->created_at)->timezone($currentMasjid->timezone)->format('j M Y H:i') }}</td>
                <td>
                    <div class="actions">
                        @if ($d->status === \App\Enums\DonationStatus::Pending)
                            <form method="POST" action="{{ route('admin.donations.complete', $d) }}" class="inline">
                                @csrf
                                <button class="btn small" type="submit">Confirm paid</button>
                            </form>
                            <form method="POST" action="{{ route('admin.donations.cancel', $d) }}" class="inline" onsubmit="return confirm('Cancel this pledge?')">
                                @csrf
                                <button class="btn small secondary" type="submit">Cancel</button>
                            </form>
                        @elseif ($d->status === \App\Enums\DonationStatus::Completed)
                            <a class="btn small secondary" href="{{ route('admin.donations.receipt', $d) }}">Receipt</a>
                            <details>
                                <summary>Refund</summary>
                                <form method="POST" action="{{ route('admin.donations.refund', $d) }}">
                                    @csrf
                                    <input name="reason" placeholder="Reason" required>
                                    <button class="btn small danger" type="submit">Refund</button>
                                </form>
                            </details>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="9" class="muted">No donations found.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
