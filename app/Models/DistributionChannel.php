<?php

namespace App\Models;

use App\Enums\DistributionChannelType;
use App\Enums\NetworkEntityStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DistributionChannel extends Model
{
    protected $fillable = [
        'code',
        'name',
        'type',
        'status',
        'description',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'type' => DistributionChannelType::class,
            'status' => NetworkEntityStatus::class,
            'meta' => 'array',
        ];
    }

    public function nodes(): HasMany
    {
        return $this->hasMany(DistributionNode::class);
    }

    public function links(): HasMany
    {
        return $this->hasMany(DistributionLink::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }
}
