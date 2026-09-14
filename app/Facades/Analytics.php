<?php

namespace App\Facades;

use App\Domain\Analytics\AnalyticsManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static array dashboard(?\Illuminate\Support\Carbon $from = null, ?\Illuminate\Support\Carbon $to = null)
 * @method static array health(?\Illuminate\Support\Carbon $from = null, ?\Illuminate\Support\Carbon $to = null)
 * @method static array scanAnomalies(?\App\Models\User $actor = null)
 * @method static array alertCenter(array $filters = [])
 * @method static \App\Domain\Analytics\KpiService kpis()
 * @method static \App\Domain\Analytics\DistributionAnomalyService anomalyDetector()
 *
 * @see AnalyticsManager
 */
class Analytics extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AnalyticsManager::class;
    }
}
