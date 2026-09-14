<?php

namespace App\Http\Controllers;

use App\Enums\ShipmentStatus;
use App\Models\Anomaly;
use App\Models\Batch;
use App\Models\ColdRoom;
use App\Models\ColdRoomMovement;
use App\Models\Organization;
use App\Models\Product;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $orgIds = $user->organizations()->pluck('organizations.id');
        $scoped = fn ($q) => $user->isAdmin() ? $q : $q->whereIn('organization_id', $orgIds);

        return view('dashboard', [
            'organizationCount' => $orgIds->count(),
            'productCount' => Product::query()->whereIn('organization_id', $orgIds)->count(),
            'batchCount' => Batch::query()->whereIn('organization_id', $orgIds)->count(),
            'coldRoomCount' => ColdRoom::query()->whereIn('organization_id', $orgIds)->count(),
            'activeShipments' => Shipment::query()
                ->whereIn('status', [
                    ShipmentStatus::Dispatched,
                    ShipmentStatus::InTransit,
                    ShipmentStatus::Arrived,
                    ShipmentStatus::Delayed,
                ])
                ->when(! $user->isAdmin(), function ($q) use ($orgIds) {
                    $q->where(function ($inner) use ($orgIds) {
                        $inner->whereIn('from_organization_id', $orgIds)
                            ->orWhereIn('to_organization_id', $orgIds);
                    });
                })
                ->count(),
            'openAlerts' => Anomaly::query()->active()->count(),
            'recentBatches' => Batch::query()
                ->with(['product', 'organization'])
                ->when(! $user->isAdmin(), fn ($q) => $q->whereIn('organization_id', $orgIds))
                ->latest()
                ->limit(5)
                ->get(),
            'recentMovements' => ColdRoomMovement::query()
                ->with(['coldRoom', 'batch'])
                ->when(! $user->isAdmin(), function ($q) use ($orgIds) {
                    $q->whereHas('coldRoom', fn ($c) => $c->whereIn('organization_id', $orgIds));
                })
                ->latest('occurred_at')
                ->limit(5)
                ->get(),
            'recentShipments' => Shipment::query()
                ->with(['fromOrganization', 'toOrganization', 'vehicle'])
                ->when(! $user->isAdmin(), function ($q) use ($orgIds) {
                    $q->where(function ($inner) use ($orgIds) {
                        $inner->whereIn('from_organization_id', $orgIds)
                            ->orWhereIn('to_organization_id', $orgIds);
                    });
                })
                ->latest()
                ->limit(5)
                ->get(),
            'recentAlerts' => Anomaly::query()
                ->active()
                ->latest('detected_at')
                ->limit(5)
                ->get(),
        ]);
    }

    public function home(): View
    {
        return view('home');
    }

    public function admin(): View
    {
        return view('admin.dashboard', [
            'organizationCount' => Organization::count(),
            'pendingOrganizations' => Organization::where('status', 'pending')->count(),
            'productCount' => Product::count(),
            'batchCount' => Batch::count(),
            'coldRoomCount' => ColdRoom::count(),
            'movementCount' => ColdRoomMovement::count(),
            'userCount' => User::count(),
            'activeShipments' => Shipment::query()->whereIn('status', [
                ShipmentStatus::Dispatched->value,
                ShipmentStatus::InTransit->value,
                ShipmentStatus::Arrived->value,
                ShipmentStatus::Delayed->value,
            ])->count(),
            'openAlerts' => Anomaly::query()->active()->count(),
            'criticalAlerts' => Anomaly::query()->active()->where('severity', 'critical')->count(),
            'recentAlerts' => Anomaly::query()->active()->latest('detected_at')->limit(6)->get(),
        ]);
    }
}
