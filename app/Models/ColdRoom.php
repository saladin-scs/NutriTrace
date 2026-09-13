<?php

namespace App\Models;

use App\Enums\ColdRoomStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ColdRoom extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'organization_id',
        'location_id',
        'responsible_user_id',
        'name',
        'code',
        'status',
        'capacity_kg',
        'target_temp_min_c',
        'target_temp_max_c',
        'humidity_min_pct',
        'humidity_max_pct',
        'description',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'status' => ColdRoomStatus::class,
            'capacity_kg' => 'decimal:3',
            'target_temp_min_c' => 'decimal:2',
            'target_temp_max_c' => 'decimal:2',
            'humidity_min_pct' => 'decimal:2',
            'humidity_max_pct' => 'decimal:2',
            'meta' => 'array',
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

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(ColdRoomMovement::class)->latest('occurred_at');
    }
}
