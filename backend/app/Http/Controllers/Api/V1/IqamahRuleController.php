<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreIqamahRuleRequest;
use App\Models\IqamahRule;
use App\Models\Masjid;
use Illuminate\Http\JsonResponse;

class IqamahRuleController extends Controller
{
    public function index(Masjid $masjid): JsonResponse
    {
        return response()->json([
            'data' => $masjid->iqamahRules()->orderBy('prayer')->orderByDesc('effective_from')->get(),
        ]);
    }

    public function store(StoreIqamahRuleRequest $request, Masjid $masjid): JsonResponse
    {
        return response()->json(['data' => $masjid->iqamahRules()->create($request->validated())], 201);
    }

    public function destroy(Masjid $masjid, IqamahRule $iqamahRule): JsonResponse
    {
        $iqamahRule->delete();

        return response()->json(status: 204);
    }
}
