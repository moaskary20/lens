<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class FilterTag extends Model
{
    protected $fillable = [
        'filter_group_id', 'slug', 'name_ar', 'name_en', 'helper_text', 'group_key',
        'vendor_type_slugs', 'synonyms', 'unit', 'min_value', 'max_value',
        'is_active', 'show_in_quick_filters', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'show_in_quick_filters' => 'boolean',
            'vendor_type_slugs' => 'array',
            'synonyms' => 'array',
            'min_value' => 'decimal:2',
            'max_value' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (FilterTag $tag): void {
            if ($tag->filter_group_id && $tag->group) {
                $tag->group_key = $tag->group->slug;
            }
        });
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(FilterGroup::class, 'filter_group_id');
    }

    public function vendors(): BelongsToMany
    {
        return $this->belongsToMany(Vendor::class, 'vendor_filter_tag');
    }
}
