<?php

namespace App\Domain\Analytics;

/**
 * Application service exposed via Analytics facade.
 */
final class AnalyticsManager
{
    public function __construct(
        private KpiService $kpis,
        private DistributionAnomalyService $anomalies,
        private AlertCenterQuery $alerts,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function dashboard(?\Illuminate\Support\Carbon $from = null, ?\Illuminate\Support\Carbon $to = null): array
    {
        return $this->kpis->dashboard($from, $to);
    }

    /**
     * @return array<string, mixed>
     */
    public function health(?\Illuminate\Support\Carbon $from = null, ?\Illuminate\Support\Carbon $to = null): array
    {
        return $this->kpis->health($from, $to);
    }

    /**
     * @return list<\App\Models\Anomaly>
     */
    public function scanAnomalies(?\App\Models\User $actor = null): array
    {
        $created = $this->anomalies->scan($actor);
        $this->kpis->forgetCache();

        return $created;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function alertCenter(array $filters = []): array
    {
        return $this->alerts->payload($filters);
    }

    public function kpis(): KpiService
    {
        return $this->kpis;
    }

    public function anomalyDetector(): DistributionAnomalyService
    {
        return $this->anomalies;
    }
}
