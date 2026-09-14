<?php

namespace App\Domain\Analytics;

use App\Enums\AnomalyCategory;
use App\Enums\AnomalySeverity;
use App\Enums\AnomalyStatus;
use App\Enums\ShipmentStatus;
use App\Enums\StockMovementType;
use App\Enums\StorageRecordStatus;
use App\Enums\TemperatureStatus;
use App\Models\Anomaly;
use App\Models\Batch;
use App\Models\ColdRoom;
use App\Models\Shipment;
use App\Models\StockMovement;
use App\Models\StorageRecord;
use App\Models\TemperatureRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Deterministic anomaly detection — flags for human verification only.
 * Never auto-accuses actors of illegal behaviour.
 */
final class DistributionAnomalyService
{
    public function __construct(
        private ColdChainAnalyticsService $coldChain,
        private MassBalanceBridge $massBalance,
    ) {}

    /**
     * Full network scan. Returns created/updated anomalies.
     *
     * @return list<Anomaly>
     */
    public function scan(?User $actor = null): array
    {
        $created = [];

        $created = array_merge($created, $this->detectDelayedShipments($actor));
        $created = array_merge($created, $this->detectTemperatureBreaches($actor));
        $created = array_merge($created, $this->detectCapacityRisks($actor));
        $created = array_merge($created, $this->detectExcessiveStorage($actor));
        $created = array_merge($created, $this->detectNearExpiry($actor));
        $created = array_merge($created, $this->detectExcessiveLosses($actor));
        $created = array_merge($created, $this->detectMassBalanceGaps($actor));
        $created = array_merge($created, $this->detectStockConcentration($actor));

        return $created;
    }

    /**
     * Targeted scan after a stock movement.
     *
     * @return list<Anomaly>
     */
    public function scanAfterStockMovement(StockMovement $movement, ?User $actor = null): array
    {
        $out = [];

        if ($movement->cold_room_id) {
            $room = ColdRoom::query()->find($movement->cold_room_id);
            if ($room) {
                $out = array_merge($out, $this->detectCapacityForRoom($room, $actor));
            }
        }

        if (in_array($movement->type, [
            StockMovementType::Loss,
            StockMovementType::Damage,
            StockMovementType::Expiration,
        ], true)) {
            $out = array_merge($out, $this->detectExcessiveLosses($actor));
        }

        if ($movement->batch_id) {
            $batch = Batch::query()->find($movement->batch_id);
            if ($batch) {
                $out = array_merge($out, $this->detectMassBalanceForBatch($batch, $actor));
            }
        }

        return $out;
    }

    /**
     * @return list<Anomaly>
     */
    public function detectDelayedShipments(?User $actor = null): array
    {
        $out = [];
        $shipments = Shipment::query()
            ->with(['fromOrganization', 'toOrganization', 'vehicle'])
            ->where(function ($q) {
                $q->where('status', ShipmentStatus::Delayed)
                    ->orWhere(function ($inner) {
                        $inner->whereIn('status', [
                            ShipmentStatus::InTransit->value,
                            ShipmentStatus::Dispatched->value,
                        ])->whereNotNull('eta_at')
                            ->where('eta_at', '<', now());
                    });
            })
            ->limit(50)
            ->get();

        foreach ($shipments as $shipment) {
            $overdue = ($shipment->eta_at && $shipment->eta_at->isPast())
                ? (int) $shipment->eta_at->diffInMinutes(now())
                : ($shipment->status === ShipmentStatus::Delayed ? 45 : 0);

            $severity = $overdue >= 120 ? AnomalySeverity::Critical : AnomalySeverity::Warning;

            $out[] = $this->upsert(
                fingerprint: 'delay:shipment:'.$shipment->id,
                category: AnomalyCategory::DeliveryDelay,
                severity: $severity,
                title: 'Retard — '.$shipment->code,
                message: sprintf(
                    'Shipment %s (%s → %s) en retard%s.',
                    $shipment->code,
                    $shipment->fromOrganization?->name ?? '?',
                    $shipment->toOrganization?->name ?? '?',
                    $overdue > 0 ? sprintf(' de %d min', $overdue) : ''
                ),
                recommendation: 'Vérifier le véhicule, le trafic et contacter le destinataire. Investigation humaine requise.',
                subject: $shipment,
                actor: $actor,
                attrs: [
                    'shipment_id' => $shipment->id,
                    'vehicle_id' => $shipment->vehicle_id,
                    'organization_id' => $shipment->from_organization_id,
                    'metric_value' => $overdue,
                    'threshold_value' => 30,
                    'evidence' => [
                        'status' => $shipment->status->value,
                        'eta_at' => $shipment->eta_at?->toIso8601String(),
                        'vehicle' => $shipment->vehicle?->registration,
                    ],
                ],
            );
        }

        return array_filter($out);
    }

    /**
     * @return list<Anomaly>
     */
    public function detectTemperatureBreaches(?User $actor = null): array
    {
        $out = [];
        $records = TemperatureRecord::query()
            ->with('coldRoom')
            ->whereIn('status', [TemperatureStatus::Warning->value, TemperatureStatus::Critical->value])
            ->where('recorded_at', '>=', now()->subDay())
            ->latest('recorded_at')
            ->limit(40)
            ->get();

        foreach ($records as $record) {
            $room = $record->coldRoom;
            if (! $room) {
                continue;
            }

            $severity = $record->status === TemperatureStatus::Critical
                ? AnomalySeverity::Critical
                : AnomalySeverity::Warning;

            $out[] = $this->upsert(
                fingerprint: 'temp:room:'.$room->id.':'.($record->recorded_at?->format('YmdHi') ?? 'x'),
                category: AnomalyCategory::TemperatureBreach,
                severity: $severity,
                title: 'Température — '.$room->code,
                message: sprintf(
                    'Chambre %s : %s°C (consigne %s→%s). Statut %s.',
                    $room->code,
                    $record->temperature_c,
                    $room->target_temp_min_c,
                    $room->target_temp_max_c,
                    $record->status->label()
                ),
                recommendation: 'Contrôler le groupe froid et l’intégrité des lots. Alerte décision-support — pas une accusation.',
                subject: $room,
                actor: $actor,
                attrs: [
                    'cold_room_id' => $room->id,
                    'organization_id' => $room->organization_id,
                    'batch_id' => $record->batch_id,
                    'metric_value' => $record->temperature_c,
                    'threshold_value' => $room->target_temp_max_c,
                    'evidence' => [
                        'temperature_record_id' => $record->id,
                        'source' => $record->source,
                        'recorded_at' => $record->recorded_at?->toIso8601String(),
                    ],
                ],
            );
        }

        return array_filter($out);
    }

    /**
     * @return list<Anomaly>
     */
    public function detectCapacityRisks(?User $actor = null): array
    {
        $out = [];
        foreach (ColdRoom::query()->where('status', 'active')->get() as $room) {
            $out = array_merge($out, $this->detectCapacityForRoom($room, $actor));
        }

        return array_filter($out);
    }

    /**
     * @return list<Anomaly>
     */
    public function detectCapacityForRoom(ColdRoom $room, ?User $actor = null): array
    {
        $rate = $room->occupancyRate();
        if ($rate < 85) {
            return [];
        }

        $severity = $rate >= 95 ? AnomalySeverity::Critical : AnomalySeverity::Warning;

        return array_filter([$this->upsert(
            fingerprint: 'capacity:room:'.$room->id,
            category: AnomalyCategory::CapacityAnomaly,
            severity: $severity,
            title: 'Capacité — '.$room->code,
            message: sprintf(
                'Occupation %.1f%% (%s / %s kg) sur %s.',
                $rate,
                $room->occupied_capacity_kg,
                $room->capacity_kg,
                $room->code
            ),
            recommendation: 'Planifier des sorties FEFO ou différer les entrées. Vérification humaine recommandée.',
            subject: $room,
            actor: $actor,
            attrs: [
                'cold_room_id' => $room->id,
                'organization_id' => $room->organization_id,
                'location_id' => $room->location_id,
                'metric_value' => $rate,
                'threshold_value' => 85,
                'evidence' => [
                    'occupied_kg' => $room->occupied_capacity_kg,
                    'capacity_kg' => $room->capacity_kg,
                ],
            ],
        )]);
    }

    /**
     * @return list<Anomaly>
     */
    public function detectExcessiveStorage(?User $actor = null, int $maxHours = 72): array
    {
        $out = [];
        $records = StorageRecord::query()
            ->with(['batch.product', 'coldRoom'])
            ->whereIn('status', [StorageRecordStatus::Stored->value, StorageRecordStatus::Partial->value])
            ->where('remaining_quantity', '>', 0)
            ->where('entered_at', '<=', now()->subHours($maxHours))
            ->limit(30)
            ->get();

        foreach ($records as $record) {
            $out[] = $this->upsert(
                fingerprint: 'storage:duration:'.$record->id,
                category: AnomalyCategory::UnusualStorageDuration,
                severity: AnomalySeverity::Info,
                title: 'Stockage prolongé — '.($record->batch?->code ?? 'lot'),
                message: sprintf(
                    '%s stocké depuis %s dans %s (%.0f %s).',
                    $record->batch?->code ?? 'Lot',
                    $record->storageDurationLabel(),
                    $record->coldRoom?->code ?? 'CF',
                    $record->remaining_quantity,
                    $record->unit
                ),
                recommendation: 'Vérifier la destination planifiée et appliquer FEFO si applicable.',
                subject: $record->coldRoom,
                actor: $actor,
                attrs: [
                    'cold_room_id' => $record->cold_room_id,
                    'batch_id' => $record->batch_id,
                    'metric_value' => $record->storageDurationMinutes() / 60,
                    'threshold_value' => $maxHours,
                    'evidence' => [
                        'storage_record_id' => $record->id,
                        'entered_at' => $record->entered_at?->toIso8601String(),
                        'product' => $record->batch?->product?->name,
                    ],
                ],
            );
        }

        return array_filter($out);
    }

    /**
     * @return list<Anomaly>
     */
    public function detectNearExpiry(?User $actor = null, int $withinDays = 3): array
    {
        $out = [];
        $records = StorageRecord::query()
            ->with(['batch.product', 'coldRoom'])
            ->whereIn('status', [StorageRecordStatus::Stored->value, StorageRecordStatus::Partial->value])
            ->where('remaining_quantity', '>', 0)
            ->whereHas('batch', fn ($q) => $q->whereNotNull('expires_at')->where('expires_at', '<=', now()->addDays($withinDays)))
            ->limit(30)
            ->get();

        foreach ($records as $record) {
            $out[] = $this->upsert(
                fingerprint: 'expiry:storage:'.$record->id,
                category: AnomalyCategory::NearExpiry,
                severity: AnomalySeverity::Warning,
                title: 'FEFO — '.($record->batch?->code ?? 'lot'),
                message: sprintf(
                    '%s expire le %s — %.0f %s encore en %s.',
                    $record->batch?->code ?? 'Lot',
                    $record->batch?->expires_at?->format('d/m/Y'),
                    $record->remaining_quantity,
                    $record->unit,
                    $record->coldRoom?->code ?? 'CF'
                ),
                recommendation: 'Prioriser la sortie (FEFO) ou redistribuer. Vérification humaine.',
                subject: $record->batch,
                actor: $actor,
                attrs: [
                    'cold_room_id' => $record->cold_room_id,
                    'batch_id' => $record->batch_id,
                    'metric_value' => $record->remaining_quantity,
                    'evidence' => [
                        'expires_at' => $record->batch?->expires_at?->toIso8601String(),
                        'product' => $record->batch?->product?->name,
                    ],
                ],
            );
        }

        return array_filter($out);
    }

    /**
     * @return list<Anomaly>
     */
    public function detectExcessiveLosses(?User $actor = null): array
    {
        $windowStart = now()->subDays(7);
        $loss = (float) StockMovement::query()
            ->whereIn('type', [
                StockMovementType::Loss->value,
                StockMovementType::Damage->value,
                StockMovementType::Expiration->value,
            ])
            ->where('occurred_at', '>=', $windowStart)
            ->sum('quantity');

        $handled = (float) StockMovement::query()
            ->where('occurred_at', '>=', $windowStart)
            ->sum('quantity');

        if ($handled <= 0) {
            return [];
        }

        $rate = ($loss / $handled) * 100;
        if ($rate < 5) {
            return [];
        }

        $severity = $rate >= 10 ? AnomalySeverity::Critical : AnomalySeverity::Warning;

        return array_filter([$this->upsert(
            fingerprint: 'loss:network:7d',
            category: AnomalyCategory::ExcessiveLoss,
            severity: $severity,
            title: 'Pertes réseau élevées',
            message: sprintf(
                'Taux de perte %.2f%% sur 7 jours (%.0f kg / %.0f kg traités).',
                $rate,
                $loss,
                $handled
            ),
            recommendation: 'Analyser les motifs de perte par nœud. Indicateur décision-support uniquement.',
            subject: null,
            actor: $actor,
            attrs: [
                'metric_value' => round($rate, 3),
                'threshold_value' => 5,
                'evidence' => [
                    'loss_kg' => $loss,
                    'handled_kg' => $handled,
                    'window_days' => 7,
                ],
            ],
        )]);
    }

    /**
     * @return list<Anomaly>
     */
    public function detectMassBalanceGaps(?User $actor = null): array
    {
        $out = [];
        $batches = Batch::query()->latest('id')->limit(25)->get();
        foreach ($batches as $batch) {
            $out = array_merge($out, $this->detectMassBalanceForBatch($batch, $actor));
        }

        return array_filter($out);
    }

    /**
     * @return list<Anomaly>
     */
    public function detectMassBalanceForBatch(Batch $batch, ?User $actor = null): array
    {
        $balance = $this->massBalance->forBatch($batch);
        if ($balance['balanced'] ?? true) {
            return [];
        }

        $unexplained = abs((float) ($balance['unexplained_kg'] ?? 0));
        if ($unexplained < 0.01) {
            return [];
        }

        $severity = $unexplained > ((float) $batch->quantity * 0.05)
            ? AnomalySeverity::Critical
            : AnomalySeverity::Warning;

        return array_filter([$this->upsert(
            fingerprint: 'mass:batch:'.$batch->id,
            category: AnomalyCategory::MassBalance,
            severity: $severity,
            title: 'Écart de masse — '.$batch->code,
            message: sprintf(
                'Écart de %.3f kg non expliqué sur %s (input %.3f, accounted %.3f).',
                $unexplained,
                $batch->code,
                $balance['input_kg'] ?? 0,
                $balance['accounted_kg'] ?? 0
            ),
            recommendation: 'Réconcilier stock / distributions / pertes. Vérification humaine — pas une accusation.',
            subject: $batch,
            actor: $actor,
            attrs: [
                'batch_id' => $batch->id,
                'organization_id' => $batch->organization_id,
                'metric_value' => $unexplained,
                'evidence' => $balance,
            ],
        )]);
    }

    /**
     * Flags high regional concentration of open cold-room stock for a product.
     * Informational only — does NOT mean illegal hoarding.
     *
     * @return list<Anomaly>
     */
    public function detectStockConcentration(?User $actor = null, float $thresholdPct = 45.0): array
    {
        $rows = StorageRecord::query()
            ->select('product_id', 'cold_room_id', DB::raw('SUM(remaining_quantity) as qty'))
            ->whereIn('status', [StorageRecordStatus::Stored->value, StorageRecordStatus::Partial->value])
            ->where('remaining_quantity', '>', 0)
            ->whereNotNull('product_id')
            ->groupBy('product_id', 'cold_room_id')
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $byProduct = $rows->groupBy('product_id');
        $out = [];

        foreach ($byProduct as $productId => $group) {
            $total = (float) $group->sum('qty');
            if ($total < 50) {
                continue;
            }

            $top = $group->sortByDesc('qty')->first();
            $share = ((float) $top->qty / $total) * 100;
            if ($share < $thresholdPct) {
                continue;
            }

            $room = ColdRoom::query()->find($top->cold_room_id);
            $product = \App\Models\Product::query()->find($productId);

            $out[] = $this->upsert(
                fingerprint: 'concentration:product:'.$productId,
                category: AnomalyCategory::StockConcentration,
                severity: AnomalySeverity::Info,
                title: 'Concentration de stock — '.($product?->name ?? 'produit'),
                message: sprintf(
                    '%.0f%% du stock ouvert de « %s » est concentré en %s (%.0f / %.0f kg).',
                    $share,
                    $product?->name ?? 'produit',
                    $room?->code ?? 'CF',
                    $top->qty,
                    $total
                ),
                recommendation: 'Modèle de distribution à vérifier. Ne signifie pas automatiquement une rétention illégale.',
                subject: $room,
                actor: $actor,
                attrs: [
                    'cold_room_id' => $room?->id,
                    'organization_id' => $room?->organization_id,
                    'metric_value' => round($share, 2),
                    'threshold_value' => $thresholdPct,
                    'evidence' => [
                        'product_id' => $productId,
                        'product' => $product?->name,
                        'room_qty' => $top->qty,
                        'total_qty' => $total,
                    ],
                ],
            );
        }

        return array_filter($out);
    }

    /**
     * @param  array<string, mixed>  $attrs
     */
    private function upsert(
        string $fingerprint,
        AnomalyCategory $category,
        AnomalySeverity $severity,
        string $title,
        string $message,
        string $recommendation,
        ?Model $subject,
        ?User $actor,
        array $attrs,
    ): ?Anomaly {
        $existing = Anomaly::query()
            ->where('fingerprint', $fingerprint)
            ->whereIn('status', [
                AnomalyStatus::Open->value,
                AnomalyStatus::Acknowledged->value,
                AnomalyStatus::Investigating->value,
            ])
            ->first();

        if ($existing) {
            $existing->update([
                'severity' => $severity,
                'title' => $title,
                'message' => $message,
                'recommendation' => $recommendation,
                'metric_value' => $attrs['metric_value'] ?? $existing->metric_value,
                'threshold_value' => $attrs['threshold_value'] ?? $existing->threshold_value,
                'evidence' => $attrs['evidence'] ?? $existing->evidence,
                'detected_at' => now(),
            ]);

            return $existing->fresh();
        }

        return Anomaly::query()->create([
            'code' => $this->nextCode(),
            'category' => $category,
            'severity' => $severity,
            'status' => AnomalyStatus::Open,
            'title' => $title,
            'message' => $message,
            'recommendation' => $recommendation,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'batch_id' => $attrs['batch_id'] ?? null,
            'shipment_id' => $attrs['shipment_id'] ?? null,
            'cold_room_id' => $attrs['cold_room_id'] ?? null,
            'vehicle_id' => $attrs['vehicle_id'] ?? null,
            'organization_id' => $attrs['organization_id'] ?? null,
            'location_id' => $attrs['location_id'] ?? null,
            'detected_by' => $actor?->id,
            'detected_at' => now(),
            'metric_value' => $attrs['metric_value'] ?? null,
            'threshold_value' => $attrs['threshold_value'] ?? null,
            'fingerprint' => $fingerprint,
            'evidence' => $attrs['evidence'] ?? null,
            'meta' => [
                'ethical_note' => 'anomaly → evidence → human verification',
            ],
        ]);
    }

    private function nextCode(): string
    {
        $year = (int) date('Y');
        $last = Anomaly::query()
            ->where('code', 'like', "AN-{$year}-%")
            ->orderByDesc('code')
            ->value('code');

        $seq = 1;
        if (is_string($last) && preg_match('/AN-'.$year.'-(\d+)$/', $last, $m)) {
            $seq = ((int) $m[1]) + 1;
        }

        return sprintf('AN-%d-%06d', $year, $seq);
    }
}
