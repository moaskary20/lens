<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FilterGroup extends Model
{
    protected $fillable = [
        'slug', 'name', 'description', 'scope', 'facet_level', 'input_type',
        'vendor_type_slugs', 'icon', 'is_active', 'is_system', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'vendor_type_slugs' => 'array',
            'is_active' => 'boolean',
            'is_system' => 'boolean',
        ];
    }

    public function options(): HasMany
    {
        return $this->hasMany(FilterTag::class)->orderBy('sort_order');
    }
}
