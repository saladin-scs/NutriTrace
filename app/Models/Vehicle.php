<?php

namespace App\Models;

use App\Enums\FuelType;
use App\Enums\VehicleStatus;
use App\Enums\VehicleType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'organization_id',
        'registration',
        'type',
        'fuel_type',
        'status',
        'capacity_kg',
        'emission_factor',
        'driver_name',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'type' => VehicleType::class,
            'fuel_type' => FuelType::class,
            'status' => VehicleStatus::class,
            'capacity_kg' => 'decimal:3',
            'emission_factor' => 'decimal:4',
            'meta' => 'array',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    public function positions(): HasMany
    {
        return $this->hasMany(VehiclePosition::class)->orderByDesc('recorded_at');
    }

    public function latestPosition(): ?VehiclePosition
    {
        return $this->positions()->first();
    }
}
