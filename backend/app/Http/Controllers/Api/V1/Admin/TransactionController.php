<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Masjid;
use App\Models\Transaction;
use App\Services\Finance\LedgerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransactionController extends Controller
{
    public function __construct(private readonly LedgerService $ledger) {}

    public function index(Request $request, Masjid $masjid): AnonymousResourceCollection
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

        return TransactionResource::collection(
            self::filtered($masjid, $filters)->with('category', 'fund', 'recorder')
                ->orderByDesc('transacted_on')->orderByDesc('id')->paginate(25)
        );
    }

    public function show(Masjid $masjid, Transaction $transaction): TransactionResource
    {
        return new TransactionResource($transaction->load('category', 'fund', 'recorder'));
    }

    public function store(StoreTransactionRequest $request, Masjid $masjid): JsonResponse
    {
        $transaction = $this->ledger->record($masjid, $request->ledgerData(), $request->user());

        return (new TransactionResource($transaction->load('category', 'fund', 'recorder')))->response()->setStatusCode(201);
    }

    public function void(Request $request, Masjid $masjid, Transaction $transaction): TransactionResource
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']]);

        return new TransactionResource($this->ledger->void($transaction, $request->user(), $data['reason'])->load('category', 'fund'));
    }

    public function attachment(Masjid $masjid, Transaction $transaction): StreamedResponse
    {
        abort_unless($transaction->attachment_path && Storage::exists($transaction->attachment_path), 404);

        return Storage::download($transaction->attachment_path);
    }

    public static function filtered(Masjid $masjid, array $filters)
    {
        return $masjid->transactions()
            ->when($filters['type'] ?? null, fn ($q, $v) => $q->where('type', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['category_id'] ?? null, fn ($q, $v) => $q->where('category_id', $v))
            ->when($filters['fund_id'] ?? null, fn ($q, $v) => $q->where('fund_id', $v))
            ->when($filters['from'] ?? null, fn ($q, $v) => $q->whereDate('transacted_on', '>=', $v))
            ->when($filters['to'] ?? null, fn ($q, $v) => $q->whereDate('transacted_on', '<=', $v))
            ->when($filters['q'] ?? null, fn ($q, $v) => $q->where(fn ($w) => $w
                ->where('reference_no', 'like', "%{$v}%")
                ->orWhere('description', 'like', "%{$v}%")
                ->orWhere('party_name', 'like', "%{$v}%")));
    }
}
