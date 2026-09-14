<?php

namespace App\Http\Controllers;

use App\Domain\Distribution\ControlTowerQuery;
use App\Facades\Analytics;
use App\Models\Anomaly;
use App\Models\Shipment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ControlTowerController extends Controller
{
    public function index(Request $request, ControlTowerQuery $query): View
    {
        $this->authorize('viewAny', Shipment::class);

        $tab = $request->string('tab')->toString() ?: 'ops';
        if (! in_array($tab, ['ops', 'intelligence'], true)) {
            $tab = 'ops';
        }

        return view('control-tower.index', [
            'tower' => $query->mapPayload(),
            'analytics' => $tab === 'intelligence' ? Analytics::dashboard() : null,
            'alertFeed' => Anomaly::query()
                ->active()
                ->with(['coldRoom', 'shipment', 'batch'])
                ->latest('detected_at')
                ->limit(8)
                ->get(),
            'tab' => $tab,
        ]);
    }

    public function feed(ControlTowerQuery $query): JsonResponse
    {
        $this->authorize('viewAny', Shipment::class);

        if (request()->hasSession()) {
            request()->session()->save();
        }

        return response()->json(['data' => $query->mapPayload()]);
    }
}
