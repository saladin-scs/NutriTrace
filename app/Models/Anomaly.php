<?php

namespace App\Models;

use App\Enums\AnomalyCategory;
use App\Enums\AnomalySeverity;
use App\Enums\AnomalyStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Anomaly extends Model
{
    protected $fillable = [
        'code',
        'category',
        'severity',
        'status',
        'title',
        'message',
        'recommendation',
        'subject_type',
        'subject_id',
        'batch_id',
        'shipment_id',
        'cold_room_id',
        'vehicle_id',
        'organization_id',
        'location_id',
        'detected_by',
        'acknowledged_by',
        'detected_at',
        'acknowledged_at',
        'resolved_at',
        'metric_value',
        'threshold_value',
        'fingerprint',
        'evidence',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'category' => AnomalyCategory::class,
            'severity' => AnomalySeverity::class,
            'status' => AnomalyStatus::class,
            'detected_at' => 'datetime',
            'acknowledged_at' => 'datetime',
            'resolved_at' => 'datetime',
            'metric_value' => 'decimal:3',
            'threshold_value' => 'decimal:3',
            'evidence' => 'array',
            'meta' => 'array',
        ];
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function coldRoom(): BelongsTo
    {
        return $this->belongsTo(ColdRoom::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function detector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'detected_by');
    }

    public function acknowledger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [
            AnomalyStatus::Open->value,
            AnomalyStatus::Acknowledged->value,
            AnomalyStatus::Investigating->value,
        ]);
    }

    public function deepLink(): ?string
    {
        if ($this->cold_room_id) {
            return route('cold-rooms.show', $this->cold_room_id);
        }
        if ($this->shipment_id) {
            return route('shipments.show', $this->shipment_id);
        }
        if ($this->batch_id) {
            return route('batches.show', $this->batch_id);
        }

        return route('alert-center.index');
    }
}
