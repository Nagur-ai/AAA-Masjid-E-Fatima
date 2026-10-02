<?php

namespace App\Services\Finance;

use App\Enums\DonationStatus;
use App\Enums\TransactionType;
use App\Models\Donation;
use App\Models\Masjid;
use App\Models\TransactionCategory;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Donation lifecycle: pending -> completed -> (refunded), or pending -> cancelled.
 * Completing a donation issues a receipt number and posts income to the ledger.
 */
class DonationService
{
    public function __construct(
        private readonly LedgerService $ledger,
        private readonly SequenceGenerator $sequences,
    ) {}

    /**
     * A donor's pledge from the web or mobile app, awaiting payment verification.
     */
    public function pledge(Masjid $masjid, array $data, ?User $donor = null): Donation
    {
        $donation = $this->make($masjid, $data);
        $donation->user()->associate($donor);
        if ($donor) {
            $donation->donor_name ??= $donor->name;
            $donation->donor_email ??= $donor->email;
            $donation->donor_phone ??= $donor->phone;
        }
        $donation->save();

        return $donation;
    }

    /**
     * Money already in hand (cash box, counter, verified transfer): record and complete at once.
     */
    public function recordReceived(Masjid $masjid, array $data, User $actor): Donation
    {
        return DB::transaction(function () use ($masjid, $data, $actor) {
            $donation = $this->make($masjid, $data);
            $donation->recorded_by = $actor->id;
            $donation->save();

            return $this->complete($donation, $actor, $data['donated_at'] ?? null);
        });
    }

    public function complete(Donation $donation, User $actor, ?string $donatedAt = null, ?string $paymentReference = null): Donation
    {
        return DB::transaction(function () use ($donation, $actor, $donatedAt, $paymentReference) {
            $donation = Donation::whereKey($donation->id)->lockForUpdate()->firstOrFail();
            $this->assertStatus($donation, DonationStatus::Pending, 'completed');

            $masjid = $donation->masjid;
            $donatedAt = $donatedAt ? Carbon::parse($donatedAt, $masjid->timezone) : Carbon::now($masjid->timezone);

            $donation->receipt_no = $this->sequences->next($masjid, 'RCP', $donatedAt->year);
            $donation->status = DonationStatus::Completed;
            // Eloquent stores datetimes without converting zones, so persist in the app timezone.
            $donation->donated_at = $donatedAt->copy()->setTimezone(config('app.timezone'));
            $donation->payment_reference = $paymentReference ?? $donation->payment_reference;
            $donation->recorded_by ??= $actor->id;

            $category = $masjid->categories()->firstOrCreate(
                ['type' => TransactionType::Income, 'name' => TransactionCategory::DONATIONS],
                ['is_system' => true],
            );

            $transaction = $this->ledger->record($masjid, [
                'type' => TransactionType::Income->value,
                'category_id' => $category->id,
                'fund_id' => $donation->fund_id,
                'amount' => $donation->amount,
                'payment_method' => $donation->payment_method->value,
                'party_name' => $donation->displayName(),
                'description' => "Donation {$donation->receipt_no} to {$donation->fund->name}",
                'transacted_on' => $donatedAt->toDateString(),
            ], $actor, $donation);

            $donation->transaction()->associate($transaction);
            $donation->save();

            return $donation;
        });
    }

    public function cancel(Donation $donation): Donation
    {
        return DB::transaction(function () use ($donation) {
            $donation = Donation::whereKey($donation->id)->lockForUpdate()->firstOrFail();
            $this->assertStatus($donation, DonationStatus::Pending, 'cancelled');

            $donation->status = DonationStatus::Cancelled;
            $donation->save();

            return $donation;
        });
    }

    public function refund(Donation $donation, User $actor, string $reason): Donation
    {
        return DB::transaction(function () use ($donation, $actor, $reason) {
            $donation = Donation::whereKey($donation->id)->lockForUpdate()->firstOrFail();
            $this->assertStatus($donation, DonationStatus::Completed, 'refunded');

            if ($donation->transaction) {
                $this->ledger->void($donation->transaction, $actor, "Refund: {$reason}", fromDonationRefund: true);
            }

            $donation->status = DonationStatus::Refunded;
            $donation->notes = trim(($donation->notes ? $donation->notes."\n" : '')."Refunded: {$reason}");
            $donation->save();

            return $donation;
        });
    }

    private function make(Masjid $masjid, array $data): Donation
    {
        $fund = $masjid->funds()->where('is_active', true)->whereKey($data['fund_id'] ?? null)->first();
        if (! $fund) {
            throw ValidationException::withMessages(['fund_id' => 'Choose an active fund of this masjid.']);
        }

        $amount = Money::normalize($data['amount'] ?? 0);
        if (bccomp($amount, '0', 2) <= 0) {
            throw ValidationException::withMessages(['amount' => 'Amount must be greater than zero.']);
        }

        $donation = new Donation([
            'fund_id' => $fund->id,
            'donor_name' => $data['donor_name'] ?? null,
            'donor_email' => $data['donor_email'] ?? null,
            'donor_phone' => $data['donor_phone'] ?? null,
            'is_anonymous' => (bool) ($data['is_anonymous'] ?? false),
            'amount' => $amount,
            'currency' => $masjid->currency,
            'payment_method' => $data['payment_method'],
            'payment_reference' => $data['payment_reference'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);
        $donation->status = DonationStatus::Pending;
        $donation->masjid()->associate($masjid);

        return $donation;
    }

    private function assertStatus(Donation $donation, DonationStatus $expected, string $pastTense): void
    {
        if ($donation->status !== $expected) {
            throw ValidationException::withMessages([
                'donation' => "Only {$expected->value} donations can be {$pastTense}; this one is {$donation->status->value}.",
            ]);
        }
    }
}
