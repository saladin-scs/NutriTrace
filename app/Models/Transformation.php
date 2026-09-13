<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transformation extends Model
{
    protected $fillable = [
        'organization_id',
        'input_batch_id',
        'output_batch_id',
        'location_id',
        'performed_by',
        'process_name',
        'input_quantity',
        'output_quantity',
        'loss_quantity',
        'unit',
        'occurred_at',
        'notes',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'input_quantity' => 'decimal:3',
            'output_quantity' => 'decimal:3',
            'loss_quantity' => 'decimal:3',
            'occurred_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function inputBatch(): BelongsTo
    {
        return $this->belongsTo(Batch::class, 'input_batch_id');
    }

    public function outputBatch(): BelongsTo
    {
        return $this->belongsTo(Batch::class, 'output_batch_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
