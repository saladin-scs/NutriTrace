<?php

namespace App\Models;

use App\Enums\DataProvenance;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class EnvironmentalMetric extends Model
{
    protected $fillable = [
        'metricable_type',
        'metricable_id',
        'provenance',
        'co2_kg',
        'water_liters',
        'distance_km',
        'packaging_score',
        'environmental_score',
        'breakdown',
        'meta',
        'measured_at',
    ];

    protected function casts(): array
    {
        return [
            'provenance' => DataProvenance::class,
            'co2_kg' => 'decimal:4',
            'water_liters' => 'decimal:4',
            'distance_km' => 'decimal:2',
            'packaging_score' => 'decimal:2',
            'environmental_score' => 'decimal:2',
            'breakdown' => 'array',
            'meta' => 'array',
            'measured_at' => 'datetime',
        ];
    }

    public function metricable(): MorphTo
    {
        return $this->morphTo();
    }
}
