<?php

namespace App\Http\Controllers\Api\V1;

use App\Facades\Analytics;
use App\Http\Controllers\Controller;
use App\Models\Anomaly;
use Illuminate\Http\JsonResponse;

class AnalyticsApiController extends Controller
{
    public function kpis(): JsonResponse
    {
        $this->authorize('viewAny', Anomaly::class);

        return response()->json(['data' => Analytics::dashboard()]);
    }

    public function health(): JsonResponse
    {
        $this->authorize('viewAny', Anomaly::class);

        return response()->json(['data' => Analytics::health()]);
    }

    public function waste(): JsonResponse
    {
        $this->authorize('viewAny', Anomaly::class);

        return response()->json(['data' => Analytics::dashboard()['waste']]);
    }

    public function coldChain(): JsonResponse
    {
        $this->authorize('viewAny', Anomaly::class);

        return response()->json(['data' => Analytics::dashboard()['cold_chain']]);
    }

    public function environment(): JsonResponse
    {
        $this->authorize('viewAny', Anomaly::class);

        return response()->json(['data' => Analytics::dashboard()['environmental']]);
    }
}
