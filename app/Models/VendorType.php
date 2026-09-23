<?php

namespace App\Models;

use App\Support\Roles;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VendorType extends Model
{
    protected $fillable = [
        'slug', 'name_ar', 'name_en', 'icon', 'pricing_model', 'pricing_model_id',
        'description', 'is_active', 'escrow_on_checkin', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'escrow_on_checkin' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (VendorType $type): void {
            if ($type->pricing_model_id) {
                $slug = PricingModel::query()->whereKey($type->pricing_model_id)->value('slug');
                if ($slug) {
                    $type->pricing_model = $slug;
                }
            }
        });
    }

    public function vendors(): HasMany
    {
        return $this->hasMany(Vendor::class);
    }

    public function pricingModel(): BelongsTo
    {
        return $this->belongsTo(PricingModel::class);
    }

    public function featureKey(): ?string
    {
        return Roles::VENDOR_TYPE_FEATURES[$this->slug] ?? null;
    }

    public function scopeMarketplace(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->whereIn('slug', Roles::enabledVendorTypeSlugs());
    }
}
