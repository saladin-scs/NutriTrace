<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;

class VehicleApiController extends Controller
{
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Shipment::class);

        $vehicles = Vehicle::query()
            ->with('organization')
            ->orderBy('registration')
            ->get()
            ->map(function (Vehicle $vehicle) {
                $position = $vehicle->positions()->first();

                return [
                    'id' => $vehicle->id,
                    'registration' => $vehicle->registration,
                    'type' => $vehicle->type->value,
                    'status' => $vehicle->status->value,
                    'driver' => $vehicle->driver_name,
                    'capacity_kg' => $vehicle->capacity_kg,
                    'organization' => $vehicle->organization?->name,
                    'lat' => $position ? (float) $position->latitude : null,
                    'lng' => $position ? (float) $position->longitude : null,
                    'temperature_c' => $position?->temperature_c,
                ];
            });

        return response()->json(['data' => $vehicles]);
    }
}
