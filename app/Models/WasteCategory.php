<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WasteCategory extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'is_valorisable',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'is_valorisable' => 'boolean',
        ];
    }

    public function wastes(): HasMany
    {
        return $this->hasMany(Waste::class);
    }
}
