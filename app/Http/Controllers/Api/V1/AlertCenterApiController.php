<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Anomalies\UpdateAnomalyStatusAction;
use App\Domain\Analytics\AlertCenterQuery;
use App\Enums\AnomalyStatus;
use App\Facades\Analytics;
use App\Http\Controllers\Controller;
use App\Models\Anomaly;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlertCenterApiController extends Controller
{
    public function index(Request $request, AlertCenterQuery $query): JsonResponse
    {
        $this->authorize('viewAny', Anomaly::class);

        return response()->json([
            'data' => $query->payload($request->only(['severity', 'category', 'status', 'q'])),
        ]);
    }

    public function scan(Request $request): JsonResponse
    {
        $this->authorize('scan', Anomaly::class);

        $created = Analytics::scanAnomalies($request->user());

        return response()->json([
            'data' => [
                'count' => count($created),
                'anomalies' => collect($created)->map(fn (Anomaly $a) => [
                    'id' => $a->id,
                    'code' => $a->code,
                    'severity' => $a->severity->value,
                    'title' => $a->title,
                ])->values(),
            ],
        ]);
    }

    public function updateStatus(
        Request $request,
        Anomaly $anomaly,
        UpdateAnomalyStatusAction $action,
        AlertCenterQuery $query,
    ): JsonResponse {
        $this->authorize('update', $anomaly);

        $data = $request->validate([
            'status' => ['required', 'in:acknowledged,investigating,resolved,dismissed'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $updated = $action->execute(
            $request->user(),
            $anomaly,
            AnomalyStatus::from($data['status']),
            $data['note'] ?? null,
        );

        return response()->json(['data' => $query->serialize($updated)]);
    }
}
