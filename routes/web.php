<?php

use App\Http\Controllers\Admin\OrganizationController as AdminOrganizationController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\AlertCenterController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\BatchController;
use App\Http\Controllers\ColdRoomController;
use App\Http\Controllers\ControlTowerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ShipmentController;
use App\Http\Controllers\TracePassportController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'home'])->name('home');
Route::get('/trace/{code}', TracePassportController::class)->name('trace.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/control-tower', [ControlTowerController::class, 'index'])->name('control-tower.index');
    Route::get('/control-tower/feed', [ControlTowerController::class, 'feed'])->name('control-tower.feed');
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
    Route::get('/alert-center', [AlertCenterController::class, 'index'])->name('alert-center.index');
    Route::get('/alert-center/{anomaly}', [AlertCenterController::class, 'show'])->name('alert-center.show');
    Route::patch('/alert-center/{anomaly}/status', [AlertCenterController::class, 'updateStatus'])->name('alert-center.status');
    Route::post('/alert-center/scan', [AlertCenterController::class, 'scan'])->name('alert-center.scan');

    Route::get('/shipments', [ShipmentController::class, 'index'])->name('shipments.index');
    Route::get('/shipments/create', [ShipmentController::class, 'create'])->name('shipments.create');
    Route::post('/shipments', [ShipmentController::class, 'store'])->name('shipments.store');
    Route::get('/shipments/{shipment}', [ShipmentController::class, 'show'])->name('shipments.show');
    Route::post('/shipments/{shipment}/dispatch', [ShipmentController::class, 'dispatchShipment'])->name('shipments.dispatch');
    Route::post('/shipments/{shipment}/receive', [ShipmentController::class, 'receive'])->name('shipments.receive');

    Route::get('/organizations', [OrganizationController::class, 'index'])->name('organizations.index');
    Route::get('/organizations/create', [OrganizationController::class, 'create'])->name('organizations.create');
    Route::post('/organizations', [OrganizationController::class, 'store'])->name('organizations.store');
    Route::get('/organizations/{organization}', [OrganizationController::class, 'show'])->name('organizations.show');
    Route::get('/organizations/{organization}/edit', [OrganizationController::class, 'edit'])->name('organizations.edit');
    Route::put('/organizations/{organization}', [OrganizationController::class, 'update'])->name('organizations.update');
    Route::post('/organizations/{organization}/members', [OrganizationController::class, 'attachMember'])->name('organizations.members.store');
    Route::delete('/organizations/{organization}/members/{user}', [OrganizationController::class, 'detachMember'])->name('organizations.members.destroy');

    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');
    Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
    Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::get('/products/{product}/batches/create', [BatchController::class, 'create'])->name('products.batches.create');
    Route::post('/products/{product}/batches', [BatchController::class, 'store'])->name('products.batches.store');

    Route::get('/batches', [BatchController::class, 'index'])->name('batches.index');
    Route::get('/batches/{batch}', [BatchController::class, 'show'])->name('batches.show');
    Route::post('/batches/{batch}/transform', [BatchController::class, 'transform'])->name('batches.transform');
    Route::post('/batches/{batch}/distribute', [BatchController::class, 'distribute'])->name('batches.distribute');

    Route::get('/cold-rooms', [ColdRoomController::class, 'index'])->name('cold-rooms.index');
    Route::get('/cold-rooms/create', [ColdRoomController::class, 'create'])->name('cold-rooms.create');
    Route::post('/cold-rooms', [ColdRoomController::class, 'store'])->name('cold-rooms.store');
    Route::get('/cold-rooms/{coldRoom}', [ColdRoomController::class, 'show'])->name('cold-rooms.show');
    Route::post('/cold-rooms/{coldRoom}/movements', [ColdRoomController::class, 'recordMovement'])->name('cold-rooms.movements.store');
    Route::post('/cold-rooms/{coldRoom}/temperature', [ColdRoomController::class, 'recordTemperature'])->name('cold-rooms.temperature.store');

    Route::get('/notifications/feed', [NotificationController::class, 'feed'])->name('notifications.feed');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'admin'])->name('dashboard');

    Route::get('/organizations', [AdminOrganizationController::class, 'index'])->name('organizations.index');
    Route::get('/organizations/create', [AdminOrganizationController::class, 'create'])->name('organizations.create');
    Route::post('/organizations', [AdminOrganizationController::class, 'store'])->name('organizations.store');
    Route::get('/organizations/{organization}', [AdminOrganizationController::class, 'show'])->name('organizations.show');
    Route::get('/organizations/{organization}/edit', [AdminOrganizationController::class, 'edit'])->name('organizations.edit');
    Route::put('/organizations/{organization}', [AdminOrganizationController::class, 'update'])->name('organizations.update');
    Route::post('/organizations/{organization}/verify', [AdminOrganizationController::class, 'verify'])->name('organizations.verify');
    Route::delete('/organizations/{organization}', [AdminOrganizationController::class, 'destroy'])->name('organizations.destroy');

    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [AdminUserController::class, 'show'])->name('users.show');
    Route::post('/users/{user}/role', [AdminUserController::class, 'assignRole'])->name('users.role');
});

require __DIR__.'/auth.php';
