<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehiclePosition extends Model
{
    protected $fillable = [
        'vehicle_id',
        'shipment_id',
        'latitude',
        'longitude',
        'speed_kmh',
        'heading',
        'temperature_c',
        'recorded_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'speed_kmh' => 'decimal:2',
            'heading' => 'decimal:2',
            'temperature_c' => 'decimal:2',
            'recorded_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }
}
