<?php

namespace App\Actions\Batches;

use App\Domain\Identity\AuditLogger;
use App\Enums\BatchStatus;
use App\Enums\TraceabilityEventType;
use App\Enums\TransportMode;
use App\Models\Batch;
use App\Models\Distribution;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RecordDistributionAction
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(
        User $actor,
        Batch $batch,
        Organization $from,
        Organization $to,
        array $data,
    ): Distribution {
        return DB::transaction(function () use ($actor, $batch, $from, $to, $data) {
            $distribution = Distribution::query()->create([
                'batch_id' => $batch->id,
                'from_organization_id' => $from->id,
                'to_organization_id' => $to->id,
                'from_location_id' => $data['from_location_id'] ?? $from->primary_location_id,
                'to_location_id' => $data['to_location_id'] ?? $to->primary_location_id,
                'quantity' => $data['quantity'] ?? $batch->quantity,
                'unit' => $data['unit'] ?? $batch->unit,
                'transport_mode' => isset($data['transport_mode'])
                    ? (is_string($data['transport_mode']) ? TransportMode::from($data['transport_mode']) : $data['transport_mode'])
                    : TransportMode::Road,
                'distance_km' => $data['distance_km'] ?? null,
                'shipped_at' => $data['shipped_at'] ?? now(),
                'received_at' => $data['received_at'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $batch->update(['status' => BatchStatus::Distributed]);

            $batch->traceabilityEvents()->create([
                'type' => TraceabilityEventType::Distribution,
                'organization_id' => $from->id,
                'location_id' => $distribution->from_location_id,
                'actor_user_id' => $actor->id,
                'occurred_at' => $distribution->shipped_at ?? now(),
                'quantity' => $distribution->quantity,
                'unit' => $distribution->unit,
                'title' => 'Expédition vers '.$to->name,
                'meta' => [
                    'to_organization_id' => $to->id,
                    'distance_km' => $distribution->distance_km,
                    'transport_mode' => $distribution->transport_mode?->value,
                ],
            ]);

            $batch->traceabilityEvents()->create([
                'type' => TraceabilityEventType::Receipt,
                'organization_id' => $to->id,
                'location_id' => $distribution->to_location_id,
                'actor_user_id' => $actor->id,
                'occurred_at' => $distribution->received_at ?? ($distribution->shipped_at ?? now()),
                'quantity' => $distribution->quantity,
                'unit' => $distribution->unit,
                'title' => 'Réception par '.$to->name,
                'meta' => ['from_organization_id' => $from->id],
            ]);

            $this->audit->log($actor, 'batch.distributed', $distribution, null, [
                'batch' => $batch->code,
                'from' => $from->id,
                'to' => $to->id,
            ]);

            return $distribution->fresh(['batch', 'fromOrganization', 'toOrganization']);
        });
    }
}
