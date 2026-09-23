<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Badge extends Model
{
    protected $fillable = [
        'slug', 'name_ar', 'name_en', 'color', 'icon', 'criteria', 'is_automatic', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_automatic' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function vendors(): BelongsToMany
    {
        return $this->belongsToMany(Vendor::class);
    }
}
