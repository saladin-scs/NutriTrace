<?php

namespace App\Domain\Analytics;

use App\Models\Anomaly;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Orchestrates reusable KPIs for dashboards, API and alerts.
 * Heavy aggregates are cached (Redis when available, else default store).
 */
final class KpiService
{
    public function __construct(
        private DistributionAnalyticsService $distribution,
        private ColdChainAnalyticsService $coldChain,
        private WasteAnalyticsService $waste,
        private TraceabilityAnalyticsService $traceability,
        private EnvironmentalAnalyticsService $environmental,
        private SupplyChainHealthScore $healthScore,
    ) {}

    /**
     * Full executive KPI payload.
     *
     * @return array<string, mixed>
     */
    public function dashboard(?Carbon $from = null, ?Carbon $to = null, int $ttlSeconds = 60): array
    {
        $from ??= now()->subDays(30)->startOfDay();
        $to ??= now();
        $key = sprintf('kpi:dashboard:%s:%s', $from->toDateString(), $to->toDateString());

        return Cache::remember($key, $ttlSeconds, function () use ($from, $to) {
            $distribution = $this->distribution->summary($from, $to);
            $cold = $this->coldChain->summary($from, $to);
            $waste = $this->waste->summary($from, $to);
            $trace = $this->traceability->summary($from, $to);
            $env = $this->environmental->summary($from, $to);

            $pillars = [
                'delivery' => $this->distribution->performanceScore($from, $to),
                'cold_chain' => $this->coldChain->complianceScore($from, $to),
                'traceability' => $this->traceability->coverageScore($from, $to),
                'waste' => $this->waste->performanceScore($from, $to),
                'environmental' => $this->environmental->performanceScore($from, $to),
            ];

            $health = $this->healthScore->compute($pillars);

            $openAnomalies = Anomaly::query()->active()->count();
            $criticalAnomalies = Anomaly::query()->active()->where('severity', 'critical')->count();

            return [
                'generated_at' => now()->toIso8601String(),
                'period' => [
                    'from' => $from->toIso8601String(),
                    'to' => $to->toIso8601String(),
                ],
                'health' => $health,
                'logistics' => $distribution,
                'cold_chain' => $cold,
                'waste' => $waste,
                'traceability' => $trace,
                'environmental' => $env,
                'alerts' => [
                    'open' => $openAnomalies,
                    'critical' => $criticalAnomalies,
                ],
                'cards' => [
                    ['key' => 'on_time', 'label' => 'Livraisons à l’heure', 'value' => $distribution['on_time_delivery_rate'].'%', 'tone' => 'good'],
                    ['key' => 'cold_compliance', 'label' => 'Conformité cold chain', 'value' => $cold['cold_chain_compliance_rate'].'%', 'tone' => 'good'],
                    ['key' => 'loss_rate', 'label' => 'Taux de perte', 'value' => $waste['loss_rate_pct'].'%', 'tone' => 'warn'],
                    ['key' => 'traceability', 'label' => 'Couverture traçabilité', 'value' => $trace['traceability_coverage_pct'].'%', 'tone' => 'good'],
                    ['key' => 'co2e', 'label' => 'CO₂e estimé', 'value' => $env['co2e_kg'].' kg', 'tone' => 'neutral'],
                    ['key' => 'health', 'label' => 'Health Score', 'value' => $health['score'].' / 100', 'tone' => 'accent'],
                ],
            ];
        });
    }

    public function forgetCache(): void
    {
        // Best-effort: clear recent dashboard keys for today/30d window.
        $to = now();
        $from = now()->subDays(30)->startOfDay();
        Cache::forget(sprintf('kpi:dashboard:%s:%s', $from->toDateString(), $to->toDateString()));
        Cache::forget(sprintf('kpi:dashboard:%s:%s', now()->toDateString(), $to->toDateString()));
    }

    public function health(?Carbon $from = null, ?Carbon $to = null): array
    {
        return $this->dashboard($from, $to)['health'];
    }
}
