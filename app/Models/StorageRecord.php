<?php

namespace App\Models;

use App\Enums\StorageRecordStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class StorageRecord extends Model
{
    protected $fillable = [
        'cold_room_id',
        'batch_id',
        'product_id',
        'quantity',
        'remaining_quantity',
        'unit',
        'status',
        'entered_at',
        'removed_at',
        'entered_by',
        'removed_by',
        'source_organization_id',
        'source_location_id',
        'destination_organization_id',
        'destination_location_id',
        'shipment_id',
        'reason',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'remaining_quantity' => 'decimal:3',
            'status' => StorageRecordStatus::class,
            'entered_at' => 'datetime',
            'removed_at' => 'datetime',
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

    public function enteredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'entered_by');
    }

    public function removedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'removed_by');
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function storageDurationMinutes(?Carbon $at = null): int
    {
        $end = $this->removed_at ?? ($at ?? now());

        return max(0, (int) $this->entered_at->diffInMinutes($end));
    }

    public function storageDurationLabel(?Carbon $at = null): string
    {
        $minutes = $this->storageDurationMinutes($at);
        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;

        if ($hours >= 24) {
            $days = intdiv($hours, 24);
            $remHours = $hours % 24;

            return sprintf('%dj %dh %dm', $days, $remHours, $mins);
        }

        return sprintf('%dh %dm', $hours, $mins);
    }
}
