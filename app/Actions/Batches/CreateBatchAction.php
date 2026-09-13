<?php

namespace App\Actions\Batches;

use App\Domain\Identity\AuditLogger;
use App\Domain\Product\BatchCodeAllocator;
use App\Enums\BatchStatus;
use App\Enums\TraceabilityEventType;
use App\Models\Batch;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateBatchAction
{
    public function __construct(
        private BatchCodeAllocator $codes,
        private AuditLogger $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(User $actor, Product $product, array $data): Batch
    {
        return DB::transaction(function () use ($actor, $product, $data) {
            $batch = Batch::query()->create([
                'product_id' => $product->id,
                'organization_id' => $product->organization_id,
                'code' => $this->codes->allocate(),
                'status' => BatchStatus::Active,
                'quantity' => $data['quantity'],
                'unit' => $data['unit'] ?? $product->unit ?? 'kg',
                'produced_at' => $data['produced_at'] ?? now(),
                'expires_at' => $data['expires_at'] ?? null,
                'production_location_id' => $data['production_location_id']
                    ?? $product->organization?->primary_location_id,
                'notes' => $data['notes'] ?? null,
            ]);

            $batch->traceabilityEvents()->create([
                'type' => TraceabilityEventType::Production,
                'organization_id' => $batch->organization_id,
                'location_id' => $batch->production_location_id,
                'actor_user_id' => $actor->id,
                'occurred_at' => $batch->produced_at ?? now(),
                'quantity' => $batch->quantity,
                'unit' => $batch->unit,
                'title' => 'Production du lot '.$batch->code,
            ]);

            $this->audit->log($actor, 'batch.created', $batch, null, [
                'code' => $batch->code,
                'product_id' => $product->id,
            ]);

            return $batch->fresh(['product', 'organization', 'traceabilityEvents']);
        });
    }
}
