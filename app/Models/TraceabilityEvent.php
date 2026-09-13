<?php

namespace App\Models;

use App\Enums\TraceabilityEventType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TraceabilityEvent extends Model
{
    protected $fillable = [
        'batch_id',
        'type',
        'organization_id',
        'location_id',
        'actor_user_id',
        'previous_event_id',
        'occurred_at',
        'quantity',
        'unit',
        'title',
        'notes',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'type' => TraceabilityEventType::class,
            'occurred_at' => 'datetime',
            'quantity' => 'decimal:3',
            'meta' => 'array',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function previousEvent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'previous_event_id');
    }
}
