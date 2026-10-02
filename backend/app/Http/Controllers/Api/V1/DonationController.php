<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\PledgeDonationRequest;
use App\Http\Resources\DonationResource;
use App\Http\Resources\FundResource;
use App\Models\Masjid;
use App\Services\Finance\DonationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Donor-facing endpoints used by the mobile app.
 */
class DonationController extends Controller
{
    public function __construct(private readonly DonationService $donations) {}

    public function funds(Masjid $masjid): AnonymousResourceCollection
    {
        return FundResource::collection($masjid->funds()->where('is_active', true)->orderBy('name')->get());
    }

    /**
     * Create a pending donation. Guests may donate; signed-in users get it in their history.
     */
    public function store(PledgeDonationRequest $request, Masjid $masjid): JsonResponse
    {
        $donation = $this->donations->pledge($masjid, $request->validated(), $request->user('sanctum'));

        return (new DonationResource($donation->load('fund', 'masjid')))
            ->additional(['payment_instructions' => $masjid->bank_details])
            ->response()
            ->setStatusCode(201);
    }

    public function mine(Request $request): AnonymousResourceCollection
    {
        return DonationResource::collection(
            $request->user()->donations()->with('fund', 'masjid')->latest()->paginate(20)
        );
    }
}
