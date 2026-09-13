<?php

namespace App\Models;

use App\Enums\DistributionNodeType;
use App\Enums\NetworkEntityStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DistributionNode extends Model
{
    protected $fillable = [
        'distribution_channel_id',
        'organization_id',
        'location_id',
        'cold_room_id',
        'node_type',
        'code',
        'name',
        'status',
        'latitude',
        'longitude',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'node_type' => DistributionNodeType::class,
            'status' => NetworkEntityStatus::class,
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'meta' => 'array',
        ];
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(DistributionChannel::class, 'distribution_channel_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function coldRoom(): BelongsTo
    {
        return $this->belongsTo(ColdRoom::class);
    }

    public function outboundLinks(): HasMany
    {
        return $this->hasMany(DistributionLink::class, 'from_node_id');
    }

    public function inboundLinks(): HasMany
    {
        return $this->hasMany(DistributionLink::class, 'to_node_id');
    }

    public function mapLatitude(): ?float
    {
        if ($this->latitude !== null) {
            return (float) $this->latitude;
        }

        return $this->location?->latitude !== null ? (float) $this->location->latitude : null;
    }

    public function mapLongitude(): ?float
    {
        if ($this->longitude !== null) {
            return (float) $this->longitude;
        }

        return $this->location?->longitude !== null ? (float) $this->location->longitude : null;
    }
}
