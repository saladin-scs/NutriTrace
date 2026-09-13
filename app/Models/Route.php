<?php

namespace App\Models;

use App\Enums\RouteStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Route extends Model
{
    protected $fillable = [
        'code',
        'name',
        'origin_node_id',
        'destination_node_id',
        'origin_location_id',
        'destination_location_id',
        'distance_km',
        'estimated_duration_min',
        'actual_duration_min',
        'stops_count',
        'status',
        'waypoints',
        'estimated_co2e_kg',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'distance_km' => 'decimal:2',
            'estimated_co2e_kg' => 'decimal:3',
            'status' => RouteStatus::class,
            'waypoints' => 'array',
            'meta' => 'array',
        ];
    }

    public function originNode(): BelongsTo
    {
        return $this->belongsTo(DistributionNode::class, 'origin_node_id');
    }

    public function destinationNode(): BelongsTo
    {
        return $this->belongsTo(DistributionNode::class, 'destination_node_id');
    }

    public function originLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'origin_location_id');
    }

    public function destinationLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'destination_location_id');
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }
}
