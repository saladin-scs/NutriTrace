<?php

namespace App\Http\Controllers\Api\V1;

use App\Facades\ColdChain;
use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\ColdRoom;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ColdChainApiController extends Controller
{
    public function twin(ColdRoom $coldRoom): JsonResponse
    {
        $this->authorize('view', $coldRoom);

        return response()->json(['data' => ColdChain::twin($coldRoom)]);
    }

    public function temperatureHistory(ColdRoom $coldRoom): JsonResponse
    {
        $this->authorize('view', $coldRoom);

        $records = $coldRoom->temperatureRecords()
            ->limit(50)
            ->get()
            ->map(fn ($r) => [
                'temperature_c' => $r->temperature_c,
                'status' => $r->status->value,
                'source' => $r->source,
                'recorded_at' => $r->recorded_at?->toIso8601String(),
            ]);

        return response()->json(['data' => $records]);
    }

    public function temperature(Request $request, ColdRoom $coldRoom): JsonResponse
    {
        $this->authorize('view', $coldRoom);

        $data = $request->validate([
            'temperature_c' => ['nullable', 'numeric'],
        ]);

        $record = ColdChain::recordSensorReading(
            $coldRoom,
            $request->user(),
            isset($data['temperature_c']) ? (float) $data['temperature_c'] : null,
        );

        return response()->json([
            'data' => [
                'id' => $record->id,
                'temperature_c' => $record->temperature_c,
                'status' => $record->status->value,
                'source' => $record->source,
                'recorded_at' => $record->recorded_at?->toIso8601String(),
                'twin' => ColdChain::twin($coldRoom->fresh()),
            ],
        ]);
    }

    public function fefo(ColdRoom $coldRoom): JsonResponse
    {
        $this->authorize('view', $coldRoom);

        return response()->json(['data' => ColdChain::fefoQueue($coldRoom)]);
    }

    public function massBalance(Batch $batch): JsonResponse
    {
        $this->authorize('view', $batch);

        return response()->json(['data' => ColdChain::massBalance($batch)]);
    }
}
