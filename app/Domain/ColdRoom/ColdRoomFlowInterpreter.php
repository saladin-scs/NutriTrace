<?php

namespace App\Domain\ColdRoom;

use App\Enums\ColdRoomMovementType;
use App\Models\ColdRoomMovement;
use Carbon\Carbon;

final class ColdRoomFlowInterpreter
{
    public function interpret(ColdRoomMovement $movement): ColdRoomFlowSnapshot
    {
        $type = $movement->type instanceof ColdRoomMovementType
            ? $movement->type
            : ColdRoomMovementType::from($movement->type);

        $conditions = null;
        if ($movement->temperature_c !== null || $movement->humidity_pct !== null) {
            $parts = [];
            if ($movement->temperature_c !== null) {
                $parts[] = $movement->temperature_c.' °C';
            }
            if ($movement->humidity_pct !== null) {
                $parts[] = $movement->humidity_pct.' % HR';
            }
            $conditions = implode(' · ', $parts);
        }

        $duration = $movement->duration_minutes !== null
            ? $this->formatDuration((int) $movement->duration_minutes)
            : null;

        $from = $this->placeLabel(
            $movement->fromOrganization?->name,
            $movement->fromLocation?->name ?? $movement->fromLocation?->city,
            $movement->fromColdRoom?->name,
        );

        $to = $this->placeLabel(
            $movement->toOrganization?->name,
            $movement->toLocation?->name ?? $movement->toLocation?->city,
            $movement->toColdRoom?->name,
        );

        return new ColdRoomFlowSnapshot(
            when: ($movement->occurred_at ?? Carbon::now())->format('d/m/Y H:i'),
            where: $movement->coldRoom?->name,
            who: $movement->actor?->name,
            from: $from,
            to: $to,
            conditions: $conditions,
            duration: $duration,
            event: $movement->event_label ?: $type->label(),
            payload: [
                'type' => $type->value,
                'quantity' => $movement->quantity,
                'unit' => $movement->unit,
                'batch_code' => $movement->batch?->code,
            ],
        );
    }

    public function formatDuration(int $minutes): string
    {
        if ($minutes < 60) {
            return $minutes.' min';
        }

        $hours = intdiv($minutes, 60);
        $rest = $minutes % 60;

        return $rest > 0 ? "{$hours} h {$rest} min" : "{$hours} h";
    }

    private function placeLabel(?string $org, ?string $location, ?string $room): ?string
    {
        $parts = array_filter([$org, $location, $room]);

        return $parts === [] ? null : implode(' · ', $parts);
    }
}
