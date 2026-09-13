<?php

namespace App\Models;

use App\Enums\CollectionRequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CollectionRequest extends Model
{
    protected $fillable = [
        'organization_id',
        'waste_category_id',
        'location_id',
        'waste_id',
        'requested_by',
        'status',
        'quantity_declared',
        'unit',
        'requested_at',
        'notes',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'status' => CollectionRequestStatus::class,
            'quantity_declared' => 'decimal:3',
            'requested_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function wasteCategory(): BelongsTo
    {
        return $this->belongsTo(WasteCategory::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function waste(): BelongsTo
    {
        return $this->belongsTo(Waste::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function collection(): HasOne
    {
        return $this->hasOne(WasteCollection::class);
    }
}
