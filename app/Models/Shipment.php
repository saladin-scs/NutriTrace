<?php

namespace App\Models;

use App\Enums\ShipmentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shipment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'status',
        'distribution_channel_id',
        'distribution_id',
        'vehicle_id',
        'route_id',
        'from_organization_id',
        'to_organization_id',
        'from_location_id',
        'to_location_id',
        'origin_node_id',
        'destination_node_id',
        'created_by',
        'total_quantity',
        'unit',
        'load_kg',
        'eta_at',
        'dispatched_at',
        'delivered_at',
        'estimated_co2e_kg',
        'current_temperature_c',
        'notes',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'status' => ShipmentStatus::class,
            'total_quantity' => 'decimal:3',
            'load_kg' => 'decimal:3',
            'estimated_co2e_kg' => 'decimal:3',
            'current_temperature_c' => 'decimal:2',
            'eta_at' => 'datetime',
            'dispatched_at' => 'datetime',
            'delivered_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(DistributionChannel::class, 'distribution_channel_id');
    }

    public function distribution(): BelongsTo
    {
        return $this->belongsTo(Distribution::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function fromOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'from_organization_id');
    }

    public function toOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'to_organization_id');
    }

    public function fromLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'from_location_id');
    }

    public function toLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'to_location_id');
    }

    public function originNode(): BelongsTo
    {
        return $this->belongsTo(DistributionNode::class, 'origin_node_id');
    }

    public function destinationNode(): BelongsTo
    {
        return $this->belongsTo(DistributionNode::class, 'destination_node_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ShipmentItem::class);
    }

    public function positions(): HasMany
    {
        return $this->hasMany(VehiclePosition::class)->orderByDesc('recorded_at');
    }
}
