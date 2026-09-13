<?php

namespace App\Models;

use App\Enums\NetworkEntityStatus;
use App\Enums\TransportMode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DistributionLink extends Model
{
    protected $fillable = [
        'distribution_channel_id',
        'from_node_id',
        'to_node_id',
        'vehicle_id',
        'distance_km',
        'estimated_duration_min',
        'transport_mode',
        'capacity_kg',
        'status',
        'environmental_impact_kg_co2e',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'distance_km' => 'decimal:2',
            'capacity_kg' => 'decimal:3',
            'environmental_impact_kg_co2e' => 'decimal:3',
            'transport_mode' => TransportMode::class,
            'status' => NetworkEntityStatus::class,
            'meta' => 'array',
        ];
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(DistributionChannel::class, 'distribution_channel_id');
    }

    public function fromNode(): BelongsTo
    {
        return $this->belongsTo(DistributionNode::class, 'from_node_id');
    }

    public function toNode(): BelongsTo
    {
        return $this->belongsTo(DistributionNode::class, 'to_node_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
