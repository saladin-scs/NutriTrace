<?php

namespace App\Actions\Batches;

use App\Domain\Identity\AuditLogger;
use App\Domain\Product\BatchCodeAllocator;
use App\Enums\BatchStatus;
use App\Enums\TraceabilityEventType;
use App\Models\Batch;
use App\Models\Organization;
use App\Models\Transformation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordTransformationAction
{
    public function __construct(
        private BatchCodeAllocator $codes,
        private AuditLogger $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(User $actor, Batch $inputBatch, Organization $transformer, array $data): Transformation
    {
        if ($inputBatch->status === BatchStatus::Transformed) {
            throw ValidationException::withMessages([
                'batch' => 'Ce lot a déjà été transformé.',
            ]);
        }

        return DB::transaction(function () use ($actor, $inputBatch, $transformer, $data) {
            $outputProductId = $data['output_product_id'] ?? $inputBatch->product_id;
            $outputQty = $data['output_quantity'] ?? $inputBatch->quantity;
            $loss = $data['loss_quantity'] ?? null;

            $outputBatch = Batch::query()->create([
                'product_id' => $outputProductId,
                'organization_id' => $transformer->id,
                'parent_batch_id' => $inputBatch->id,
                'code' => $this->codes->allocate(),
                'status' => BatchStatus::Active,
                'quantity' => $outputQty,
                'unit' => $data['unit'] ?? $inputBatch->unit,
                'produced_at' => $data['occurred_at'] ?? now(),
                'expires_at' => $data['expires_at'] ?? $inputBatch->expires_at,
                'production_location_id' => $data['location_id'] ?? $transformer->primary_location_id,
                'notes' => $data['notes'] ?? null,
            ]);

            $inputBatch->update(['status' => BatchStatus::Transformed]);

            $transformation = Transformation::query()->create([
                'organization_id' => $transformer->id,
                'input_batch_id' => $inputBatch->id,
                'output_batch_id' => $outputBatch->id,
                'location_id' => $data['location_id'] ?? $transformer->primary_location_id,
                'performed_by' => $actor->id,
                'process_name' => $data['process_name'],
                'input_quantity' => $data['input_quantity'] ?? $inputBatch->quantity,
                'output_quantity' => $outputQty,
                'loss_quantity' => $loss,
                'unit' => $data['unit'] ?? $inputBatch->unit,
                'occurred_at' => $data['occurred_at'] ?? now(),
                'notes' => $data['notes'] ?? null,
            ]);

            $inputBatch->traceabilityEvents()->create([
                'type' => TraceabilityEventType::Transformation,
                'organization_id' => $transformer->id,
                'location_id' => $transformation->location_id,
                'actor_user_id' => $actor->id,
                'occurred_at' => $transformation->occurred_at,
                'quantity' => $transformation->input_quantity,
                'unit' => $transformation->unit,
                'title' => 'Transformation → '.$outputBatch->code,
                'meta' => ['output_batch_id' => $outputBatch->id, 'process' => $transformation->process_name],
            ]);

            $outputBatch->traceabilityEvents()->create([
                'type' => TraceabilityEventType::Production,
                'organization_id' => $transformer->id,
                'location_id' => $transformation->location_id,
                'actor_user_id' => $actor->id,
                'occurred_at' => $transformation->occurred_at,
                'quantity' => $outputQty,
                'unit' => $transformation->unit,
                'title' => 'Lot issu de transformation ('.$inputBatch->code.')',
                'meta' => ['input_batch_id' => $inputBatch->id, 'process' => $transformation->process_name],
            ]);

            $this->audit->log($actor, 'batch.transformed', $transformation, null, [
                'input' => $inputBatch->code,
                'output' => $outputBatch->code,
            ]);

            return $transformation->fresh(['inputBatch', 'outputBatch', 'organization']);
        });
    }
}
