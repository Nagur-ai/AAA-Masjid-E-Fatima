<?php

namespace App\Http\Controllers\Web\Admin;

use App\Enums\PaymentMethod;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Http\Controllers\Api\V1\Admin\TransactionController as ApiTransactionController;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransactionRequest;
use App\Models\Transaction;
use App\Services\Finance\LedgerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransactionController extends Controller
{
    public function __construct(private readonly LedgerService $ledger) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'type' => ['nullable', Rule::enum(TransactionType::class)],
            'status' => ['nullable', Rule::enum(TransactionStatus::class)],
            'category_id' => ['nullable', 'integer'],
            'fund_id' => ['nullable', 'integer'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);
        $masjid = $this->masjid();

        return view('admin.transactions.index', [
            'transactions' => ApiTransactionController::filtered($masjid, $filters)
                ->with('category', 'fund', 'recorder')
                ->orderByDesc('transacted_on')->orderByDesc('id')
                ->paginate(25)->withQueryString(),
            'categories' => $masjid->categories()->orderBy('name')->get(),
            'funds' => $masjid->funds()->orderBy('name')->get(),
            'filters' => $filters,
        ]);
    }

    public function create(Request $request): View
    {
        $masjid = $this->masjid();

        return view('admin.transactions.create', [
            'type' => $request->query('type') === 'income' ? TransactionType::Income : TransactionType::Expense,
            'categories' => $masjid->categories()->orderBy('name')->get(),
            'funds' => $masjid->funds()->where('is_active', true)->orderBy('name')->get(),
            'balances' => $this->ledger->balances($masjid->id),
            'methods' => PaymentMethod::cases(),
        ]);
    }

    public function store(StoreTransactionRequest $request): RedirectResponse
    {
        $transaction = $this->ledger->record($this->masjid(), $request->ledgerData(), $request->user());

        return redirect()->route('admin.transactions.index')
            ->with('status', "Posted {$transaction->type->value} {$transaction->reference_no}.");
    }

    public function void(Request $request, Transaction $transaction): RedirectResponse
    {
        $this->ensureOwned($transaction);
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']]);
        $this->ledger->void($transaction, $request->user(), $data['reason']);

        return back()->with('status', "{$transaction->reference_no} voided.");
    }

    public function attachment(Transaction $transaction): StreamedResponse
    {
        $this->ensureOwned($transaction);
        abort_unless($transaction->attachment_path && Storage::exists($transaction->attachment_path), 404);

        return Storage::download($transaction->attachment_path);
    }
}
