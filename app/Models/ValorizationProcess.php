<?php

namespace App\Models;

use App\Enums\ValorizationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ValorizationProcess extends Model
{
    protected $fillable = [
        'waste_collection_id',
        'waste_id',
        'treatment_center_id',
        'process_type',
        'quantity_in',
        'quantity_valorized',
        'unit',
        'processed_at',
        'output_description',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'process_type' => ValorizationType::class,
            'quantity_in' => 'decimal:3',
            'quantity_valorized' => 'decimal:3',
            'processed_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function wasteCollection(): BelongsTo
    {
        return $this->belongsTo(WasteCollection::class);
    }

    public function waste(): BelongsTo
    {
        return $this->belongsTo(Waste::class);
    }

    public function treatmentCenter(): BelongsTo
    {
        return $this->belongsTo(TreatmentCenter::class);
    }
}
