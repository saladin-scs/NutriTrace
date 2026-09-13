<?php

namespace App\Models;

use App\Enums\BatchStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Batch extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_id',
        'organization_id',
        'parent_batch_id',
        'code',
        'status',
        'quantity',
        'unit',
        'produced_at',
        'expires_at',
        'production_location_id',
        'notes',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'status' => BatchStatus::class,
            'quantity' => 'decimal:3',
            'produced_at' => 'datetime',
            'expires_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function parentBatch(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_batch_id');
    }

    public function childBatches(): HasMany
    {
        return $this->hasMany(self::class, 'parent_batch_id');
    }

    public function productionLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'production_location_id');
    }

    public function traceabilityEvents(): HasMany
    {
        return $this->hasMany(TraceabilityEvent::class)->orderBy('occurred_at');
    }

    public function distributions(): HasMany
    {
        return $this->hasMany(Distribution::class);
    }

    public function certifications(): MorphMany
    {
        return $this->morphMany(Certification::class, 'certifiable');
    }

    public function environmentalMetrics(): MorphMany
    {
        return $this->morphMany(EnvironmentalMetric::class, 'metricable');
    }
}
