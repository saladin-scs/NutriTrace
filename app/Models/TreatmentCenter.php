<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TreatmentCenter extends Model
{
    protected $fillable = [
        'organization_id',
        'location_id',
        'name',
        'capacity_kg_per_day',
        'accepted_category_slugs',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'capacity_kg_per_day' => 'decimal:3',
            'accepted_category_slugs' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function collections(): HasMany
    {
        return $this->hasMany(WasteCollection::class);
    }
}
