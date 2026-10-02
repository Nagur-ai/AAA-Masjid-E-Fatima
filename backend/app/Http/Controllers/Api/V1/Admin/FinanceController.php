<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFundRequest;
use App\Http\Resources\FundResource;
use App\Models\Fund;
use App\Models\Masjid;
use App\Services\Finance\ReportService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

/**
 * Funds, categories and reports for treasurers.
 */
class FinanceController extends Controller
{
    public function funds(Masjid $masjid): AnonymousResourceCollection
    {
        return FundResource::collection($masjid->funds()->orderBy('name')->get());
    }

    public function storeFund(StoreFundRequest $request, Masjid $masjid): JsonResponse
    {
        return (new FundResource($masjid->funds()->create($request->validated())))->response()->setStatusCode(201);
    }

    public function updateFund(StoreFundRequest $request, Masjid $masjid, Fund $fund): FundResource
    {
        $fund->update($request->validated());

        return new FundResource($fund);
    }

    public function categories(Masjid $masjid): JsonResponse
    {
        return response()->json([
            'data' => $masjid->categories()->orderBy('type')->orderBy('name')->get(['id', 'name', 'type', 'is_system']),
        ]);
    }

    public function storeCategory(Request $request, Masjid $masjid): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::enum(TransactionType::class)],
            'name' => ['required', 'string', 'max:80',
                Rule::unique('transaction_categories')->where('masjid_id', $masjid->id)->where('type', $request->input('type'))],
        ]);

        return response()->json(['data' => $masjid->categories()->create($data)], 201);
    }

    public function summary(Request $request, Masjid $masjid, ReportService $reports): JsonResponse
    {
        $today = CarbonImmutable::now($masjid->timezone);
        $data = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        return response()->json(['data' => $reports->summary(
            $masjid,
            $data['from'] ?? $today->startOfMonth()->toDateString(),
            $data['to'] ?? $today->toDateString(),
        )]);
    }
}
