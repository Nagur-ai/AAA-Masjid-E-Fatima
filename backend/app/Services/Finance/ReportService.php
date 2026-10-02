<?php

namespace App\Services\Finance;

use App\Enums\DonationStatus;
use App\Enums\TransactionType;
use App\Models\Masjid;
use App\Models\Transaction;

class ReportService
{
    public function __construct(private readonly LedgerService $ledger) {}

    /**
     * Income/expense summary for a date range (inclusive), plus fund balances as of $to.
     */
    public function summary(Masjid $masjid, string $from, string $to): array
    {
        $inRange = fn () => Transaction::query()
            ->where('transactions.masjid_id', $masjid->id)
            ->posted()
            ->whereDate('transacted_on', '>=', $from)
            ->whereDate('transacted_on', '<=', $to);

        $byCategory = $inRange()
            ->join('transaction_categories as c', 'c.id', '=', 'transactions.category_id')
            ->groupBy('c.name', 'transactions.type')
            ->selectRaw('c.name as category, transactions.type as type, SUM(transactions.amount) as total, COUNT(*) as entries')
            ->orderBy('transactions.type')
            ->orderByDesc('total')
            ->toBase()
            ->get()
            ->map(fn ($r) => [
                'category' => $r->category,
                'type' => $r->type,
                'total' => Money::normalize($r->total),
                'entries' => (int) $r->entries,
            ]);

        $income = $byCategory->where('type', TransactionType::Income->value)
            ->reduce(fn ($sum, $r) => bcadd($sum, $r['total'], 2), '0.00');
        $expense = $byCategory->where('type', TransactionType::Expense->value)
            ->reduce(fn ($sum, $r) => bcadd($sum, $r['total'], 2), '0.00');

        $monthly = [];
        foreach ($inRange()->select(['transacted_on', 'type', 'amount'])->orderBy('transacted_on')->lazy() as $t) {
            $key = $t->transacted_on->format('Y-m');
            $monthly[$key] ??= ['month' => $key, 'income' => '0.00', 'expense' => '0.00'];
            $monthly[$key][$t->type->value] = bcadd($monthly[$key][$t->type->value], $t->amount, 2);
        }

        $balances = $this->ledger->balances($masjid->id, upTo: $to);
        $funds = $masjid->funds()->orderBy('name')->get()->map(fn ($fund) => [
            'id' => $fund->id,
            'name' => $fund->name,
            'type' => $fund->type->value,
            'is_restricted' => $fund->is_restricted,
            'target_amount' => $fund->target_amount,
            'balance' => $balances[$fund->id] ?? '0.00',
        ]);

        $donations = $masjid->donations()
            ->where('status', DonationStatus::Completed)
            ->whereHas('transaction', fn ($q) => $q->whereDate('transacted_on', '>=', $from)->whereDate('transacted_on', '<=', $to))
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(amount), 0) as total')
            ->toBase()
            ->first();

        return [
            'currency' => $masjid->currency,
            'from' => $from,
            'to' => $to,
            'income' => $income,
            'expense' => $expense,
            'net' => bcsub($income, $expense, 2),
            'donations' => ['count' => (int) $donations->count, 'total' => Money::normalize($donations->total)],
            'by_category' => $byCategory->values()->all(),
            'monthly' => array_values($monthly),
            'funds' => $funds->all(),
        ];
    }
}
