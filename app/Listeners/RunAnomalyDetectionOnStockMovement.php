<?php

namespace App\Listeners;

use App\Domain\Analytics\DistributionAnomalyService;
use App\Domain\Analytics\KpiService;
use App\Events\AnomalyDetected;
use App\Events\StockMovementRecorded;

class RunAnomalyDetectionOnStockMovement
{
    public function __construct(
        private DistributionAnomalyService $detector,
        private KpiService $kpis,
    ) {}

    public function handle(StockMovementRecorded $event): void
    {
        $created = $this->detector->scanAfterStockMovement($event->movement);

        foreach ($created as $anomaly) {
            AnomalyDetected::dispatch($anomaly);
        }

        if ($created !== []) {
            $this->kpis->forgetCache();
        }
    }
}
