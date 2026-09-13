<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WasteCollection extends Model
{
    protected $fillable = [
        'collection_request_id',
        'collector_organization_id',
        'treatment_center_id',
        'collected_by',
        'vehicle',
        'scheduled_at',
        'collected_at',
        'quantity_collected',
        'unit',
        'status',
        'notes',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'collected_at' => 'datetime',
            'quantity_collected' => 'decimal:3',
            'meta' => 'array',
        ];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(CollectionRequest::class, 'collection_request_id');
    }

    public function collectorOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'collector_organization_id');
    }

    public function treatmentCenter(): BelongsTo
    {
        return $this->belongsTo(TreatmentCenter::class);
    }

    public function collector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by');
    }

    public function valorizationProcesses(): HasMany
    {
        return $this->hasMany(ValorizationProcess::class);
    }
}
