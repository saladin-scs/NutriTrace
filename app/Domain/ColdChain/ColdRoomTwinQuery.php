<?php

namespace App\Domain\ColdChain;

use App\Enums\StockMovementType;
use App\Enums\StorageRecordStatus;
use App\Enums\TemperatureStatus;
use App\Models\ColdRoom;
use App\Models\StockMovement;
use App\Models\StorageRecord;
use App\Models\TemperatureRecord;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Digital twin snapshot for a cold room — decision-support, not accusations.
 */
final class ColdRoomTwinQuery
{
    public function __construct(
        private OccupancyService $occupancy,
        private FefoService $fefo,
        private TemperatureClassifier $classifier,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(ColdRoom $room): array
    {
        $room->loadMissing(['organization', 'ownerOrganization', 'location', 'responsible']);
        $this->occupancy->recalculate($room);
        $room = $room->fresh(['organization', 'ownerOrganization', 'location', 'responsible']);

        $todayStart = Carbon::today();

        $inboundToday = (float) StockMovement::query()
            ->where('cold_room_id', $room->id)
            ->whereDate('occurred_at', $todayStart)
            ->whereIn('type', [StockMovementType::StockIn->value, StockMovementType::Reception->value])
            ->sum('quantity');

        $outboundToday = (float) StockMovement::query()
            ->where('cold_room_id', $room->id)
            ->whereDate('occurred_at', $todayStart)
            ->whereIn('type', [
                StockMovementType::StockOut->value,
                StockMovementType::Shipment->value,
                StockMovementType::Loss->value,
            ])
            ->sum('quantity');

        $openRecords = StorageRecord::query()
            ->with(['batch.product', 'enteredBy'])
            ->where('cold_room_id', $room->id)
            ->whereIn('status', [
                StorageRecordStatus::Stored->value,
                StorageRecordStatus::Partial->value,
            ])
            ->where('remaining_quantity', '>', 0)
            ->orderBy('entered_at')
            ->get();

        $avgDurationHours = $openRecords->avg(fn (StorageRecord $r) => $r->storageDurationMinutes() / 60);

        $nearExpiry = $this->fefo->nearExpiryWarnings($room);
        $excessive = $this->fefo->excessiveStorageWarnings($room);
        $nearExpiryQty = collect($nearExpiry)->sum(fn ($w) => (float) $w['remaining_quantity']);

        $latestTemp = TemperatureRecord::query()
            ->where('cold_room_id', $room->id)
            ->latest('recorded_at')
            ->first();

        $tempStatus = $latestTemp?->status
            ?? ($room->current_temperature_c !== null
                ? $this->classifier->classify($room, (float) $room->current_temperature_c)
                : TemperatureStatus::Normal);

        $capacityRisk = $this->occupancy->projectInbound($room, 0);
        $scheduledRisk = $this->occupancy->projectInbound($room, max(0, $inboundToday));

        $alerts = [];
        if ($room->occupancyRate() >= 90) {
            $alerts[] = [
                'severity' => 'warning',
                'category' => 'CAPACITY_ANOMALY',
                'message' => sprintf('Occupation %.1f%% — capacité saturante. Vérification humaine recommandée.', $room->occupancyRate()),
            ];
        }
        if ($scheduledRisk['exceeds']) {
            $alerts[] = [
                'severity' => 'critical',
                'category' => 'CAPACITY_ANOMALY',
                'message' => sprintf(
                    'Risque capacité : projection %.1f%% si les entrées du jour se maintiennent.',
                    $scheduledRisk['projected_rate']
                ),
            ];
        }
        if (in_array($tempStatus, [TemperatureStatus::Warning, TemperatureStatus::Critical], true)) {
            $alerts[] = [
                'severity' => $tempStatus === TemperatureStatus::Critical ? 'critical' : 'warning',
                'category' => 'TEMPERATURE',
                'message' => sprintf(
                    'Température %s°C hors consigne (%s → %s).',
                    $room->current_temperature_c,
                    $room->target_temp_min_c,
                    $room->target_temp_max_c
                ),
            ];
        }
        foreach ($nearExpiry as $w) {
            $alerts[] = [
                'severity' => 'warning',
                'category' => 'NEAR_EXPIRY',
                'message' => sprintf('FEFO — %s expire bientôt (%.0f %s).', $w['batch_code'], $w['remaining_quantity'], $w['unit']),
            ];
        }
        foreach (array_slice($excessive, 0, 3) as $w) {
            $alerts[] = [
                'severity' => 'info',
                'category' => 'UNUSUAL_STORAGE_DURATION',
                'message' => sprintf('%s stocké depuis %s — à vérifier.', $w['batch_code'], $w['stored_for']),
            ];
        }

        return [
            'room' => [
                'id' => $room->id,
                'code' => $room->code,
                'name' => $room->name,
                'type' => $room->type?->value,
                'type_label' => $room->type?->label(),
                'status' => $room->status->value,
                'status_label' => $room->status->label(),
                'operator' => $room->organization?->name,
                'owner' => $room->ownerOrganization?->name ?? $room->organization?->name,
                'location' => $room->location?->city,
                'governorate' => $room->location?->governorate,
                'latitude' => $room->location?->latitude,
                'longitude' => $room->location?->longitude,
                'responsible' => $room->responsible?->name,
            ],
            'kpis' => [
                'capacity_kg' => (float) $room->capacity_kg,
                'occupied_kg' => (float) $room->occupied_capacity_kg,
                'available_kg' => $room->availableCapacityKg(),
                'occupancy_pct' => $room->occupancyRate(),
                'temperature_c' => $room->current_temperature_c !== null
                    ? (float) $room->current_temperature_c
                    : null,
                'temperature_status' => $tempStatus->value,
                'temperature_status_label' => $tempStatus->label(),
                'target_min_c' => $room->target_temp_min_c,
                'target_max_c' => $room->target_temp_max_c,
                'inbound_today_kg' => round($inboundToday, 3),
                'outbound_today_kg' => round($outboundToday, 3),
                'avg_storage_days' => $avgDurationHours !== null
                    ? round($avgDurationHours / 24, 1)
                    : 0,
                'near_expiry_kg' => round($nearExpiryQty, 3),
                'energy_kwh_day' => $room->energy_kwh_day,
                'alerts_count' => count($alerts),
            ],
            'open_stock' => $openRecords->map(fn (StorageRecord $r) => [
                'id' => $r->id,
                'batch_code' => $r->batch?->code,
                'batch_id' => $r->batch_id,
                'product' => $r->batch?->product?->name ?? $r->product?->name,
                'remaining_quantity' => $r->remaining_quantity,
                'unit' => $r->unit,
                'entered_at' => $r->entered_at?->toIso8601String(),
                'stored_for' => $r->storageDurationLabel(),
                'stored_since' => $r->entered_at?->format('d/m/Y H:i'),
                'entered_by' => $r->enteredBy?->name,
                'expires_at' => $r->batch?->expires_at?->toIso8601String(),
            ])->values(),
            'fefo_queue' => $this->fefo->priorityDispatchQueue($room)->take(10)->map(fn (StorageRecord $r) => [
                'batch_code' => $r->batch?->code,
                'product' => $r->batch?->product?->name,
                'remaining_quantity' => $r->remaining_quantity,
                'expires_at' => $r->batch?->expires_at?->format('d/m/Y'),
            ])->values(),
            'alerts' => $alerts,
            'capacity_projection' => $scheduledRisk,
            'generated_at' => now()->toIso8601String(),
            'disclaimer' => 'Indicateurs de décision-support. Les alertes appellent une vérification humaine — elles ne constituent pas une accusation.',
        ];
    }
}
