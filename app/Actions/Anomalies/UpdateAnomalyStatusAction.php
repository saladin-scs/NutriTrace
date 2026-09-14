<?php

namespace App\Actions\Anomalies;

use App\Domain\Analytics\KpiService;
use App\Domain\Identity\AuditLogger;
use App\Enums\AnomalyStatus;
use App\Models\Anomaly;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class UpdateAnomalyStatusAction
{
    public function __construct(
        private AuditLogger $audit,
        private KpiService $kpis,
    ) {}

    public function execute(User $actor, Anomaly $anomaly, AnomalyStatus $status, ?string $note = null): Anomaly
    {
        if (! in_array($status, [
            AnomalyStatus::Acknowledged,
            AnomalyStatus::Investigating,
            AnomalyStatus::Resolved,
            AnomalyStatus::Dismissed,
        ], true)) {
            throw new InvalidArgumentException('Transition de statut non autorisée.');
        }

        return DB::transaction(function () use ($actor, $anomaly, $status, $note) {
            $old = $anomaly->status->value;

            $payload = [
                'status' => $status,
                'meta' => array_merge($anomaly->meta ?? [], array_filter([
                    'last_note' => $note,
                    'last_actor_id' => $actor->id,
                ])),
            ];

            if ($status === AnomalyStatus::Acknowledged || $status === AnomalyStatus::Investigating) {
                $payload['acknowledged_by'] = $actor->id;
                $payload['acknowledged_at'] = $anomaly->acknowledged_at ?? now();
            }

            if (in_array($status, [AnomalyStatus::Resolved, AnomalyStatus::Dismissed], true)) {
                $payload['resolved_at'] = now();
                $payload['acknowledged_by'] = $anomaly->acknowledged_by ?? $actor->id;
                $payload['acknowledged_at'] = $anomaly->acknowledged_at ?? now();
            }

            $anomaly->update($payload);

            $this->audit->log($actor, 'anomaly.status_updated', $anomaly, ['status' => $old], [
                'status' => $status->value,
                'note' => $note,
            ]);

            $this->kpis->forgetCache();

            return $anomaly->fresh();
        });
    }
}
