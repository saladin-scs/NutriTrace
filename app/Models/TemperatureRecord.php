<?php

namespace App\Models;

use App\Enums\TemperatureStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TemperatureRecord extends Model
{
    protected $fillable = [
        'cold_room_id',
        'batch_id',
        'temperature_c',
        'min_threshold_c',
        'max_threshold_c',
        'status',
        'source',
        'recorded_at',
        'recorded_by',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'temperature_c' => 'decimal:2',
            'min_threshold_c' => 'decimal:2',
            'max_threshold_c' => 'decimal:2',
            'status' => TemperatureStatus::class,
            'recorded_at' => 'datetime',
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

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
