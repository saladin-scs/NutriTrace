<?php

use App\Http\Controllers\Api\V1\AlertCenterApiController;
use App\Http\Controllers\Api\V1\AnalyticsApiController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BatchTimelineApiController;
use App\Http\Controllers\Api\V1\ColdChainApiController;
use App\Http\Controllers\Api\V1\ControlTowerApiController;
use App\Http\Controllers\Api\V1\OrganizationController;
use App\Http\Controllers\Api\V1\ShipmentApiController;
use App\Http\Controllers\Api\V1\TraceabilityController;
use App\Http\Controllers\Api\V1\VehicleApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::get('traceability/{code}', [TraceabilityController::class, 'traceability'])->name('api.v1.traceability.show');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/user', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        Route::get('organizations', [OrganizationController::class, 'index'])->name('api.v1.organizations.index');
        Route::post('organizations', [OrganizationController::class, 'store'])->name('api.v1.organizations.store');
        Route::get('organizations/{organization}', [OrganizationController::class, 'show'])->name('api.v1.organizations.show');
        Route::put('organizations/{organization}', [OrganizationController::class, 'update'])->name('api.v1.organizations.update');
        Route::post('organizations/{organization}/verify', [OrganizationController::class, 'verify'])->name('api.v1.organizations.verify');
        Route::post('organizations/{organization}/members', [OrganizationController::class, 'attachMember'])->name('api.v1.organizations.members.store');
        Route::delete('organizations/{organization}/members/{user}', [OrganizationController::class, 'detachMember'])->name('api.v1.organizations.members.destroy');

        Route::get('products', [TraceabilityController::class, 'products'])->name('api.v1.products.index');
        Route::post('products', [TraceabilityController::class, 'storeProduct'])->name('api.v1.products.store');
        Route::get('products/{product}', [TraceabilityController::class, 'showProduct'])->name('api.v1.products.show');
        Route::post('products/{product}/batches', [TraceabilityController::class, 'storeBatch'])->name('api.v1.products.batches.store');
        Route::get('batches', [TraceabilityController::class, 'batches'])->name('api.v1.batches.index');
        Route::get('batches/{batch}', [TraceabilityController::class, 'showBatch'])->name('api.v1.batches.show');
        Route::get('batches/{batch}/timeline', [BatchTimelineApiController::class, 'show'])->name('api.v1.batches.timeline');
        Route::post('batches/{batch}/transform', [TraceabilityController::class, 'transform'])->name('api.v1.batches.transform');
        Route::post('batches/{batch}/distribute', [TraceabilityController::class, 'distribute'])->name('api.v1.batches.distribute');

        Route::get('shipments', [ShipmentApiController::class, 'index'])->name('api.v1.shipments.index');
        Route::post('shipments', [ShipmentApiController::class, 'store'])->name('api.v1.shipments.store');
        Route::get('shipments/{shipment}', [ShipmentApiController::class, 'show'])->name('api.v1.shipments.show');
        Route::post('shipments/{shipment}/dispatch', [ShipmentApiController::class, 'dispatch'])->name('api.v1.shipments.dispatch');
        Route::post('shipments/{shipment}/receive', [ShipmentApiController::class, 'receive'])->name('api.v1.shipments.receive');
        Route::get('shipments/{shipment}/tracking', [ShipmentApiController::class, 'tracking'])->name('api.v1.shipments.tracking');

        Route::get('vehicles', [VehicleApiController::class, 'index'])->name('api.v1.vehicles.index');
        Route::get('dashboard/control-tower', [ControlTowerApiController::class, 'show'])->name('api.v1.control-tower');

        Route::get('analytics/kpis', [AnalyticsApiController::class, 'kpis'])->name('api.v1.analytics.kpis');
        Route::get('analytics/health', [AnalyticsApiController::class, 'health'])->name('api.v1.analytics.health');
        Route::get('analytics/waste', [AnalyticsApiController::class, 'waste'])->name('api.v1.analytics.waste');
        Route::get('analytics/cold-chain', [AnalyticsApiController::class, 'coldChain'])->name('api.v1.analytics.cold-chain');
        Route::get('analytics/environment', [AnalyticsApiController::class, 'environment'])->name('api.v1.analytics.environment');

        Route::get('alerts', [AlertCenterApiController::class, 'index'])->name('api.v1.alerts.index');
        Route::post('alerts/scan', [AlertCenterApiController::class, 'scan'])->name('api.v1.alerts.scan');
        Route::patch('alerts/{anomaly}', [AlertCenterApiController::class, 'updateStatus'])->name('api.v1.alerts.update');

        Route::get('cold-rooms/{coldRoom}/twin', [ColdChainApiController::class, 'twin'])->name('api.v1.cold-rooms.twin');
        Route::get('cold-rooms/{coldRoom}/temperature', [ColdChainApiController::class, 'temperatureHistory'])->name('api.v1.cold-rooms.temperature.index');
        Route::post('cold-rooms/{coldRoom}/temperature', [ColdChainApiController::class, 'temperature'])->name('api.v1.cold-rooms.temperature');
        Route::get('cold-rooms/{coldRoom}/fefo', [ColdChainApiController::class, 'fefo'])->name('api.v1.cold-rooms.fefo');
        Route::get('batches/{batch}/mass-balance', [ColdChainApiController::class, 'massBalance'])->name('api.v1.batches.mass-balance');
    });
});
