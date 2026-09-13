<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\OrganizationController;
use App\Http\Controllers\Api\V1\TraceabilityController;
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
        Route::post('batches/{batch}/transform', [TraceabilityController::class, 'transform'])->name('api.v1.batches.transform');
        Route::post('batches/{batch}/distribute', [TraceabilityController::class, 'distribute'])->name('api.v1.batches.distribute');
    });
});
