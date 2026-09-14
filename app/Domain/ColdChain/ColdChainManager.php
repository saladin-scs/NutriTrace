<?php

namespace App\Domain\ColdChain;

use App\Domain\ColdChain\ColdRoomTwinQuery;
use App\Domain\ColdChain\FefoService;
use App\Domain\ColdChain\MassBalanceService;
use App\Domain\ColdChain\OccupancyService;
use App\Domain\ColdChain\TemperatureClassifier;
use App\Enums\TemperatureStatus;
use App\Enums\TraceabilityEventType;
use App\Infrastructure\IoT\TemperatureSensorContract;
use App\Models\Batch;
use App\Models\ColdRoom;
use App\Models\TemperatureRecord;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Application service exposed via ColdChain facade.
 */
final class ColdChainManager
{
    public function __construct(
        private OccupancyService $occupancy,
        private FefoService $fefo,
        private ColdRoomTwinQuery $twinQuery,
        private MassBalanceService $massBalanceService,
        private TemperatureClassifier $classifier,
        private TemperatureSensorContract $sensor,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function twin(ColdRoom $room): array
    {
        return $this->twinQuery->build($room);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function fefoQueue(ColdRoom $room, ?float $neededKg = null): array
    {
        return $this->fefo->priorityDispatchQueue($room, $neededKg)
            ->map(fn ($r) => [
                'storage_record_id' => $r->id,
                'batch_code' => $r->batch?->code,
                'product' => $r->batch?->product?->name,
                'remaining_quantity' => $r->remaining_quantity,
                'expires_at' => $r->batch?->expires_at?->toIso8601String(),
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function massBalance(Batch $batch): array
    {
        return $this->massBalanceService->forBatch($batch);
    }

    public function recordSensorReading(ColdRoom $room, ?User $actor = null, ?float $override = null): TemperatureRecord
    {
        return DB::transaction(function () use ($room, $actor, $override) {
            $temp = $override ?? $this->sensor->read($room);
            $status = $this->classifier->classify($room, $temp);

            $record = TemperatureRecord::query()->create([
                'cold_room_id' => $room->id,
                'temperature_c' => $temp,
                'min_threshold_c' => $room->target_temp_min_c,
                'max_threshold_c' => $room->target_temp_max_c,
                'status' => $status,
                'source' => $override === null ? 'sensor' : 'manual',
                'recorded_at' => now(),
                'recorded_by' => $actor?->id,
            ]);

            $room->update(['current_temperature_c' => $temp]);

            if (in_array($status, [TemperatureStatus::Warning, TemperatureStatus::Critical], true)) {
                // Attach temperature check events to open batches for audit trail.
                $room->openStorageRecords()->with('batch')->limit(5)->get()->each(function ($storage) use ($room, $actor, $temp, $status) {
                    if (! $storage->batch) {
                        return;
                    }
                    $storage->batch->traceabilityEvents()->create([
                        'type' => TraceabilityEventType::TemperatureChecked,
                        'organization_id' => $room->organization_id,
                        'location_id' => $room->location_id,
                        'actor_user_id' => $actor?->id,
                        'occurred_at' => now(),
                        'title' => 'Contrôle T° '.$room->code.' — '.$temp.'°C',
                        'meta' => [
                            'cold_room_id' => $room->id,
                            'temperature_c' => $temp,
                            'status' => $status->value,
                        ],
                    ]);
                });
            }

            return $record;
        });
    }

    public function recalculateOccupancy(ColdRoom $room): ColdRoom
    {
        return $this->occupancy->recalculate($room);
    }

    public function occupancy(): OccupancyService
    {
        return $this->occupancy;
    }
}
