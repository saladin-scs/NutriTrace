<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Distribution\ControlTowerQuery;
use App\Http\Controllers\Controller;
use App\Models\Shipment;
use Illuminate\Http\JsonResponse;

class ControlTowerApiController extends Controller
{
    public function show(ControlTowerQuery $query): JsonResponse
    {
        $this->authorize('viewAny', Shipment::class);

        return response()->json(['data' => $query->mapPayload()]);
    }
}
