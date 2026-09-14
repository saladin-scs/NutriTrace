<?php

namespace App\Models;

use App\Enums\ColdRoomStatus;
use App\Enums\ColdRoomType;
use App\Enums\StorageRecordStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ColdRoom extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'organization_id',
        'owner_organization_id',
        'location_id',
        'responsible_user_id',
        'name',
        'code',
        'type',
        'status',
        'declared_status',
        'capacity_kg',
        'occupied_capacity_kg',
        'target_temp_min_c',
        'target_temp_max_c',
        'current_temperature_c',
        'humidity_min_pct',
        'humidity_max_pct',
        'energy_kwh_day',
        'last_inspection_at',
        'description',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'type' => ColdRoomType::class,
            'status' => ColdRoomStatus::class,
            'capacity_kg' => 'decimal:3',
            'occupied_capacity_kg' => 'decimal:3',
            'target_temp_min_c' => 'decimal:2',
            'target_temp_max_c' => 'decimal:2',
            'current_temperature_c' => 'decimal:2',
            'humidity_min_pct' => 'decimal:2',
            'humidity_max_pct' => 'decimal:2',
            'energy_kwh_day' => 'decimal:2',
            'last_inspection_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function ownerOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'owner_organization_id');
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

    public function storageRecords(): HasMany
    {
        return $this->hasMany(StorageRecord::class);
    }

    public function openStorageRecords(): HasMany
    {
        return $this->hasMany(StorageRecord::class)
            ->whereIn('status', [
                StorageRecordStatus::Stored->value,
                StorageRecordStatus::Partial->value,
            ]);
    }

    public function temperatureRecords(): HasMany
    {
        return $this->hasMany(TemperatureRecord::class)->latest('recorded_at');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class)->latest('occurred_at');
    }

    public function availableCapacityKg(): float
    {
        return max(0, (float) $this->capacity_kg - (float) $this->occupied_capacity_kg);
    }

    public function occupancyRate(): float
    {
        $capacity = (float) $this->capacity_kg;
        if ($capacity <= 0) {
            return 0.0;
        }

        return round(((float) $this->occupied_capacity_kg / $capacity) * 100, 1);
    }
}
