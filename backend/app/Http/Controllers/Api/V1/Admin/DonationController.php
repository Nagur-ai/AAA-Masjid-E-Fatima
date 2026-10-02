<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\DonationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\RecordDonationRequest;
use App\Http\Resources\DonationResource;
use App\Models\Donation;
use App\Models\Masjid;
use App\Services\Finance\DonationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class DonationController extends Controller
{
    public function __construct(private readonly DonationService $donations) {}

    public function index(Request $request, Masjid $masjid): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::enum(DonationStatus::class)],
            'fund_id' => ['nullable', 'integer'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        return DonationResource::collection(
            self::filtered($masjid, $filters)->with('fund')->latest('id')->paginate(25)
        );
    }

    public function show(Masjid $masjid, Donation $donation): DonationResource
    {
        return new DonationResource($donation->load('fund', 'transaction'));
    }

    public function store(RecordDonationRequest $request, Masjid $masjid): JsonResponse
    {
        $donation = $this->donations->recordReceived($masjid, $request->validated(), $request->user());

        return (new DonationResource($donation->load('fund', 'transaction')))->response()->setStatusCode(201);
    }

    public function complete(Request $request, Masjid $masjid, Donation $donation): DonationResource
    {
        $data = $request->validate([
            'payment_reference' => ['nullable', 'string', 'max:100'],
            'donated_at' => ['nullable', 'date', 'before_or_equal:now'],
        ]);

        $donation = $this->donations->complete($donation, $request->user(), $data['donated_at'] ?? null, $data['payment_reference'] ?? null);

        return new DonationResource($donation->load('fund', 'transaction'));
    }

    public function cancel(Masjid $masjid, Donation $donation): DonationResource
    {
        return new DonationResource($this->donations->cancel($donation)->load('fund'));
    }

    public function refund(Request $request, Masjid $masjid, Donation $donation): DonationResource
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']]);

        return new DonationResource($this->donations->refund($donation, $request->user(), $data['reason'])->load('fund', 'transaction'));
    }

    /**
     * Shared by the API and the web admin list.
     */
    public static function filtered(Masjid $masjid, array $filters)
    {
        return $masjid->donations()
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['fund_id'] ?? null, fn ($q, $v) => $q->where('fund_id', $v))
            ->when($filters['from'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($filters['to'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->when($filters['q'] ?? null, fn ($q, $v) => $q->where(fn ($w) => $w
                ->where('receipt_no', 'like', "%{$v}%")
                ->orWhere('donor_name', 'like', "%{$v}%")
                ->orWhere('donor_phone', 'like', "%{$v}%")
                ->orWhere('payment_reference', 'like', "%{$v}%")));
    }
}
