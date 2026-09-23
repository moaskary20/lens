<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PricingModel extends Model
{
    protected $fillable = [
        'slug', 'name_en', 'name_ar', 'description', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function fields(): HasMany
    {
        return $this->hasMany(PricingModelField::class)->orderBy('sort_order');
    }

    public function vendorTypes(): HasMany
    {
        return $this->hasMany(VendorType::class);
    }

    /**
     * @return array<string, string>
     */
    public function packageOptions(): array
    {
        return $this->fields
            ->mapWithKeys(fn (PricingModelField $field): array => [
                $field->package_type => $field->label,
            ])
            ->all();
    }
}
