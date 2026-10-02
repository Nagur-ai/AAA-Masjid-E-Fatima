<?php

namespace App\Services\Finance;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Donation;
use App\Models\Fund;
use App\Models\Masjid;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The only writer of the transactions table. Posted entries are immutable;
 * corrections are made by voiding and re-entering.
 */
class LedgerService
{
    public function __construct(private readonly SequenceGenerator $sequences) {}

    /**
     * @param  array{type: string, category_id: int, fund_id?: int|null, amount: string|float, payment_method: string,
     *               party_name?: string|null, description: string, transacted_on: string, attachment_path?: string|null}  $data
     */
    public function record(Masjid $masjid, array $data, User $actor, ?Model $source = null): Transaction
    {
        return DB::transaction(function () use ($masjid, $data, $actor, $source) {
            $type = TransactionType::from($data['type']);
            $amount = Money::normalize($data['amount']);

            if (bccomp($amount, '0', 2) <= 0) {
                throw ValidationException::withMessages(['amount' => 'Amount must be greater than zero.']);
            }

            $category = $masjid->categories()->whereKey($data['category_id'])->first();
            if (! $category || $category->type !== $type) {
                throw ValidationException::withMessages(['category_id' => "Choose a valid {$type->value} category."]);
            }

            $fund = null;
            if (! empty($data['fund_id'])) {
                // Lock the fund so concurrent expenses cannot overdraw it.
                $fund = $masjid->funds()->whereKey($data['fund_id'])->lockForUpdate()->first();
                if (! $fund) {
                    throw ValidationException::withMessages(['fund_id' => 'Choose a valid fund.']);
                }
                if ($type === TransactionType::Expense && $fund->is_restricted) {
                    $balance = $this->fundBalance($fund);
                    if (bccomp($amount, $balance, 2) > 0) {
                        throw ValidationException::withMessages([
                            'amount' => "The restricted fund \"{$fund->name}\" only holds {$balance}.",
                        ]);
                    }
                }
            }

            $transaction = new Transaction([
                'reference_no' => $this->sequences->next($masjid, 'TXN', (int) substr($data['transacted_on'], 0, 4)),
                'type' => $type,
                'category_id' => $category->id,
                'fund_id' => $fund?->id,
                'amount' => $amount,
                'payment_method' => $data['payment_method'],
                'party_name' => $data['party_name'] ?? null,
                'description' => $data['description'],
                'transacted_on' => $data['transacted_on'],
                'status' => TransactionStatus::Posted,
                'attachment_path' => $data['attachment_path'] ?? null,
                'recorded_by' => $actor->id,
            ]);
            $transaction->masjid()->associate($masjid);
            if ($source) {
                $transaction->source()->associate($source);
            }
            $transaction->save();

            return $transaction;
        });
    }

    /**
     * @param  bool  $fromDonationRefund  Donation income may only be voided through a donation refund.
     */
    public function void(Transaction $transaction, User $actor, string $reason, bool $fromDonationRefund = false): Transaction
    {
        return DB::transaction(function () use ($transaction, $actor, $reason, $fromDonationRefund) {
            $transaction = Transaction::whereKey($transaction->id)->lockForUpdate()->firstOrFail();

            if ($transaction->isVoided()) {
                throw ValidationException::withMessages(['transaction' => 'This transaction is already voided.']);
            }

            if ($transaction->source_type === (new Donation)->getMorphClass() && ! $fromDonationRefund) {
                throw ValidationException::withMessages(['transaction' => 'Donation entries are reversed by refunding the donation.']);
            }

            if ($transaction->type === TransactionType::Income && $transaction->fund_id) {
                $fund = Fund::whereKey($transaction->fund_id)->lockForUpdate()->first();
                if ($fund->is_restricted && bccomp($transaction->amount, $this->fundBalance($fund), 2) > 0) {
                    throw ValidationException::withMessages([
                        'transaction' => "Voiding this would overdraw the restricted fund \"{$fund->name}\".",
                    ]);
                }
            }

            $transaction->forceFill([
                'status' => TransactionStatus::Voided,
                'voided_by' => $actor->id,
                'voided_at' => now(),
                'void_reason' => $reason,
            ])->save();

            return $transaction;
        });
    }

    public function fundBalance(Fund $fund): string
    {
        return $this->balances($fund->masjid_id, [$fund->id])[$fund->id] ?? '0.00';
    }

    /**
     * Posted income minus posted expense per fund.
     *
     * @param  list<int>|null  $fundIds
     * @return array<int, string>
     */
    public function balances(int $masjidId, ?array $fundIds = null, ?string $upTo = null): array
    {
        $rows = Transaction::query()
            ->where('masjid_id', $masjidId)
            ->posted()
            ->whereNotNull('fund_id')
            ->when($fundIds !== null, fn ($q) => $q->whereIn('fund_id', $fundIds))
            ->when($upTo !== null, fn ($q) => $q->whereDate('transacted_on', '<=', $upTo))
            ->groupBy('fund_id', 'type')
            ->selectRaw('fund_id, type, SUM(amount) as total')
            ->toBase()
            ->get();

        $balances = [];
        foreach ($rows as $row) {
            $current = $balances[$row->fund_id] ?? '0.00';
            $total = Money::normalize($row->total);
            $balances[$row->fund_id] = $row->type === TransactionType::Income->value
                ? bcadd($current, $total, 2)
                : bcsub($current, $total, 2);
        }

        return $balances;
    }
}
