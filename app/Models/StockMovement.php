<?php

namespace App\Models;

use App\Enums\StockMovementType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    protected $fillable = [
        'type',
        'product_id',
        'batch_id',
        'quantity',
        'unit',
        'actor_user_id',
        'occurred_at',
        'cold_room_id',
        'warehouse_location_id',
        'source_organization_id',
        'source_location_id',
        'destination_organization_id',
        'destination_location_id',
        'vehicle_id',
        'shipment_id',
        'storage_record_id',
        'reference_document',
        'reason',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'type' => StockMovementType::class,
            'quantity' => 'decimal:3',
            'occurred_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function coldRoom(): BelongsTo
    {
        return $this->belongsTo(ColdRoom::class);
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function storageRecord(): BelongsTo
    {
        return $this->belongsTo(StorageRecord::class);
    }
}
