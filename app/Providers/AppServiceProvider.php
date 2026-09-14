<?php

namespace App\Providers;

use App\Domain\Analytics\AnalyticsManager;
use App\Domain\ColdChain\ColdChainManager;
use App\Facades\Analytics;
use App\Facades\ColdChain;
use App\Infrastructure\IoT\SimulatedTemperatureSensor;
use App\Infrastructure\IoT\TemperatureSensorContract;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TemperatureSensorContract::class, SimulatedTemperatureSensor::class);
        $this->app->singleton(ColdChainManager::class);
        $this->app->singleton(AnalyticsManager::class);

        $loader = AliasLoader::getInstance();
        $loader->alias('ColdChain', ColdChain::class);
        $loader->alias('Analytics', Analytics::class);
    }

    public function boot(): void
    {
        Event::listen(
            \App\Events\StockMovementRecorded::class,
            \App\Listeners\SyncColdRoomOccupancy::class,
        );

        Event::listen(
            \App\Events\StockMovementRecorded::class,
            \App\Listeners\RunAnomalyDetectionOnStockMovement::class,
        );
    }
}
