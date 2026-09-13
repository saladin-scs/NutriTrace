<?php

namespace App\Models;

use App\Enums\TransportMode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Distribution extends Model
{
    protected $fillable = [
        'batch_id',
        'from_organization_id',
        'to_organization_id',
        'from_location_id',
        'to_location_id',
        'quantity',
        'unit',
        'transport_mode',
        'distance_km',
        'shipped_at',
        'received_at',
        'notes',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'distance_km' => 'decimal:2',
            'transport_mode' => TransportMode::class,
            'shipped_at' => 'datetime',
            'received_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
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
}
