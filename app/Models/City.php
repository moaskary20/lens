<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    protected $fillable = [
        'name_ar', 'name_en', 'country', 'governorate', 'latitude', 'longitude', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (City $city): void {
            $city->country = $city->country ?: 'EG';
            $city->governorate = $city->governorate ?: $city->name_en;
        });
    }

    public function vendors(): HasMany
    {
        return $this->hasMany(Vendor::class);
    }
}
