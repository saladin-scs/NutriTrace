<?php

namespace App\Models;

use App\Enums\WasteStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Waste extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'organization_id',
        'waste_category_id',
        'batch_id',
        'product_id',
        'location_id',
        'declared_by',
        'status',
        'quantity',
        'unit',
        'generated_at',
        'notes',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'status' => WasteStatus::class,
            'quantity' => 'decimal:3',
            'generated_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(WasteCategory::class, 'waste_category_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function declarant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'declared_by');
    }

    public function collectionRequests(): HasMany
    {
        return $this->hasMany(CollectionRequest::class);
    }
}
