<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Traceability\TraceabilityChainBuilder;
use App\Http\Controllers\Controller;
use App\Models\Batch;
use Illuminate\Http\JsonResponse;

class BatchTimelineApiController extends Controller
{
    public function show(Batch $batch, TraceabilityChainBuilder $chain): JsonResponse
    {
        $this->authorize('view', $batch);

        $batch->load(['traceabilityEvents.organization', 'traceabilityEvents.location', 'traceabilityEvents.actor']);

        $events = $batch->traceabilityEvents->map(fn ($event) => [
            'id' => $event->id,
            'type' => $event->type->value,
            'type_label' => $event->type->label(),
            'title' => $event->title,
            'occurred_at' => $event->occurred_at?->toIso8601String(),
            'organization' => $event->organization?->name,
            'location' => $event->location?->city,
            'actor' => $event->actor?->name,
            'quantity' => $event->quantity,
            'unit' => $event->unit,
            'meta' => $event->meta,
        ]);

        return response()->json([
            'data' => [
                'batch_code' => $batch->code,
                'timeline' => $events,
                'chain' => $chain->build($batch),
            ],
        ]);
    }
}
