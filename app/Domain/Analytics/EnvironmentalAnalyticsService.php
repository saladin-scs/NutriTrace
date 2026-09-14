<?php

namespace App\Domain\Analytics;

use App\Models\EnvironmentalMetric;
use App\Models\Shipment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

/**
 * Environmental estimates — clearly marked as assumption-based.
 */
final class EnvironmentalAnalyticsService
{
    /**
     * @return array<string, mixed>
     */
    public function summary(?Carbon $from = null, ?Carbon $to = null): array
    {
        $from ??= now()->subDays(30)->startOfDay();
        $to ??= now();

        $shipments = Shipment::query()
            ->whereBetween('created_at', [$from, $to])
            ->get(['estimated_co2e_kg', 'load_kg', 'route_id']);

        $co2 = (float) $shipments->sum(fn (Shipment $s) => (float) ($s->estimated_co2e_kg ?? 0));
        $load = (float) $shipments->sum(fn (Shipment $s) => (float) ($s->load_kg ?? 0));
        $distance = 0.0;

        if (Schema::hasTable('routes')) {
            $routeIds = $shipments->pluck('route_id')->filter()->unique();
            $distance = (float) \App\Models\Route::query()
                ->whereIn('id', $routeIds)
                ->sum('distance_km');
        }

        $co2PerKg = $load > 0 ? round($co2 / $load, 4) : null;
        $co2PerShipment = $shipments->count() > 0
            ? round($co2 / $shipments->count(), 3)
            : null;

        $metricCo2 = 0.0;
        if (Schema::hasTable('environmental_metrics')) {
            $metricCo2 = (float) EnvironmentalMetric::query()->sum('co2_kg');
        }

        return [
            'co2e_kg' => round(max($co2, $metricCo2), 3),
            'co2e_per_kg' => $co2PerKg,
            'co2e_per_shipment' => $co2PerShipment,
            'distance_km' => round($distance, 2),
            'shipments_counted' => $shipments->count(),
            'disclaimer' => 'Estimation basée sur distance × facteur d’émission × load factor. Hypothèses configurables — pas une mesure certifiée.',
        ];
    }

    /**
     * Higher is better: reward lower CO2 intensity relative to a soft baseline.
     */
    public function performanceScore(?Carbon $from = null, ?Carbon $to = null): float
    {
        $s = $this->summary($from, $to);
        $perShipment = $s['co2e_per_shipment'];

        if ($perShipment === null || $s['shipments_counted'] === 0) {
            return 80.0; // neutral when no data
        }

        // Soft baseline 150 kg CO2e / shipment → score 50; lower is better
        $baseline = 150.0;
        $ratio = $perShipment / $baseline;
        $score = 100 - min(100, $ratio * 50);

        return max(0.0, min(100.0, round($score, 1)));
    }
}
