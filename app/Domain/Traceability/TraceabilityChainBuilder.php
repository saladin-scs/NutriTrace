<?php

namespace App\Domain\Traceability;

use App\Enums\TraceabilityEventType;
use App\Models\Batch;
use App\Models\TraceabilityEvent;
use Illuminate\Support\Collection;

final class TraceabilityChainBuilder
{
    /**
     * @return list<TraceabilityNode>
     */
    public function build(Batch $batch): array
    {
        $events = $batch->relationLoaded('traceabilityEvents')
            ? $batch->traceabilityEvents
            : $batch->traceabilityEvents()->with(['organization', 'location'])->orderBy('occurred_at')->get();

        return $events
            ->map(fn (TraceabilityEvent $event) => $this->toNode($event))
            ->values()
            ->all();
    }

    /**
     * Remonte la lignée parent → lot courant pour le passeport.
     *
     * @return Collection<int, Batch>
     */
    public function lineage(Batch $batch): Collection
    {
        $chain = collect([$batch]);
        $current = $batch;

        while ($current->parent_batch_id) {
            $parent = Batch::query()
                ->with('organization')
                ->find($current->parent_batch_id);

            if (! $parent) {
                break;
            }

            $chain->prepend($parent);
            $current = $parent;
        }

        return $chain->values();
    }

    private function toNode(TraceabilityEvent $event): TraceabilityNode
    {
        $location = $event->location;
        $locationLabel = $location
            ? trim(implode(', ', array_filter([$location->city, $location->governorate])))
            : null;

        return new TraceabilityNode(
            type: $event->type instanceof TraceabilityEventType ? $event->type : TraceabilityEventType::from($event->type),
            label: $event->title ?: $this->defaultLabel($event->type),
            occurredAt: $event->occurred_at?->toIso8601String(),
            organizationName: $event->organization?->name,
            locationLabel: $locationLabel ?: null,
            meta: [
                'quantity' => $event->quantity,
                'unit' => $event->unit,
                'notes' => $event->notes,
            ],
        );
    }

    private function defaultLabel(TraceabilityEventType|string $type): string
    {
        $enum = $type instanceof TraceabilityEventType ? $type : TraceabilityEventType::from($type);

        return $enum->label();
    }
}
