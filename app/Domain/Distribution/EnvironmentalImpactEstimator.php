<?php

namespace App\Domain\Distribution;

use App\Models\Vehicle;

/**
 * Estimates CO2e for a shipment leg.
 * Formula (documented estimate): distance_km × emission_factor × load_factor
 * load_factor = clamp(load_kg / capacity_kg, 0.3, 1.2) or 1.0 if unknown.
 */
final class EnvironmentalImpactEstimator
{
    public function estimateCo2eKg(
        ?float $distanceKm,
        ?Vehicle $vehicle,
        ?float $loadKg = null,
    ): ?float {
        if ($distanceKm === null || $distanceKm <= 0) {
            return null;
        }

        $factor = $vehicle?->emission_factor !== null
            ? (float) $vehicle->emission_factor
            : 0.85;

        $loadFactor = 1.0;
        $capacity = $vehicle?->capacity_kg !== null ? (float) $vehicle->capacity_kg : null;
        if ($capacity && $capacity > 0 && $loadKg !== null) {
            $loadFactor = max(0.3, min(1.2, $loadKg / $capacity));
        }

        return round($distanceKm * $factor * $loadFactor, 3);
    }
}
