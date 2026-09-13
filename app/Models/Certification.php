<?php

namespace App\Models;

use App\Enums\CertificationStatus;
use App\Enums\DataProvenance;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Certification extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'certifiable_type',
        'certifiable_id',
        'type',
        'organism',
        'number',
        'issued_at',
        'expires_at',
        'status',
        'verification_level',
        'verified_by',
        'verified_at',
        'notes',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'date',
            'expires_at' => 'date',
            'status' => CertificationStatus::class,
            'verification_level' => DataProvenance::class,
            'verified_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function certifiable(): MorphTo
    {
        return $this->morphTo();
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CertificationDocument::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
