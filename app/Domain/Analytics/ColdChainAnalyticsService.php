<?php

namespace App\Domain\Analytics;

use App\Enums\TemperatureStatus;
use App\Models\ColdRoom;
use App\Models\StorageRecord;
use App\Models\TemperatureRecord;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class ColdChainAnalyticsService
{
    /**
     * @return array<string, mixed>
     */
    public function summary(?Carbon $from = null, ?Carbon $to = null): array
    {
        $from ??= now()->subDays(7)->startOfDay();
        $to ??= now();

        $rooms = ColdRoom::query()->where('status', 'active')->get();
        $active = $rooms->count();
        $avgOccupancy = $rooms->avg(fn (ColdRoom $r) => $r->occupancyRate()) ?? 0;
        $saturated = $rooms->filter(fn (ColdRoom $r) => $r->occupancyRate() >= 90)->count();

        $readings = TemperatureRecord::query()
            ->whereBetween('recorded_at', [$from, $to]);

        $totalReadings = (clone $readings)->count();
        $breaches = (clone $readings)
            ->whereIn('status', [TemperatureStatus::Warning->value, TemperatureStatus::Critical->value])
            ->count();
        $critical = (clone $readings)
            ->where('status', TemperatureStatus::Critical->value)
            ->count();

        $compliance = $totalReadings > 0
            ? round((($totalReadings - $breaches) / $totalReadings) * 100, 1)
            : 100.0;

        $avgStorageHours = StorageRecord::query()
            ->whereIn('status', ['stored', 'partial'])
            ->where('remaining_quantity', '>', 0)
            ->get()
            ->avg(fn (StorageRecord $r) => $r->storageDurationMinutes() / 60) ?? 0;

        return [
            'active_cold_rooms' => $active,
            'avg_occupancy_pct' => round((float) $avgOccupancy, 1),
            'saturated_rooms' => $saturated,
            'temperature_readings' => $totalReadings,
            'temperature_breaches' => $breaches,
            'temperature_critical' => $critical,
            'cold_chain_compliance_rate' => $compliance,
            'average_storage_hours' => round((float) $avgStorageHours, 1),
            'average_storage_days' => round((float) $avgStorageHours / 24, 1),
            'open_storage_kg' => (float) StorageRecord::query()
                ->whereIn('status', ['stored', 'partial'])
                ->sum('remaining_quantity'),
        ];
    }

    public function complianceScore(?Carbon $from = null, ?Carbon $to = null): float
    {
        $s = $this->summary($from, $to);
        $compliance = (float) $s['cold_chain_compliance_rate'];
        $satPenalty = min(25.0, (float) $s['saturated_rooms'] * 8);

        return max(0.0, min(100.0, $compliance - $satPenalty));
    }
}
