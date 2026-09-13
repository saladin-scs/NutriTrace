<?php

namespace App\Models;

use App\Enums\ColdRoomMovementType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ColdRoomMovement extends Model
{
    protected $fillable = [
        'cold_room_id',
        'batch_id',
        'product_id',
        'type',
        'occurred_at',
        'actor_user_id',
        'from_organization_id',
        'from_location_id',
        'from_cold_room_id',
        'to_organization_id',
        'to_location_id',
        'to_cold_room_id',
        'quantity',
        'unit',
        'temperature_c',
        'humidity_pct',
        'duration_minutes',
        'event_label',
        'notes',
        'traceability_event_id',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'type' => ColdRoomMovementType::class,
            'occurred_at' => 'datetime',
            'quantity' => 'decimal:3',
            'temperature_c' => 'decimal:2',
            'humidity_pct' => 'decimal:2',
            'meta' => 'array',
        ];
    }

    public function coldRoom(): BelongsTo
    {
        return $this->belongsTo(ColdRoom::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function fromOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'from_organization_id');
    }

    public function fromLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'from_location_id');
    }

    public function fromColdRoom(): BelongsTo
    {
        return $this->belongsTo(ColdRoom::class, 'from_cold_room_id');
    }

    public function toOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'to_organization_id');
    }

    public function toLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'to_location_id');
    }

    public function toColdRoom(): BelongsTo
    {
        return $this->belongsTo(ColdRoom::class, 'to_cold_room_id');
    }

    public function traceabilityEvent(): BelongsTo
    {
        return $this->belongsTo(TraceabilityEvent::class);
    }
}
