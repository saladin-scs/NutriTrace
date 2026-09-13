<?php

namespace App\Http\Controllers;

use App\Domain\Distribution\ControlTowerQuery;
use App\Models\Shipment;
use Illuminate\View\View;

class ControlTowerController extends Controller
{
    public function index(ControlTowerQuery $query): View
    {
        $this->authorize('viewAny', Shipment::class);

        return view('control-tower.index', [
            'tower' => $query->mapPayload(),
        ]);
    }
}
