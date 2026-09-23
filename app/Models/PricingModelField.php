<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PricingModelField extends Model
{
    public const VENDOR_COLUMNS = [
        'half_day_price',
        'full_day_price',
        'hourly_price',
        'per_video_price',
    ];

    protected $fillable = [
        'pricing_model_id', 'key', 'storage_key', 'label', 'package_type',
        'unit', 'duration_hours', 'helper_text', 'sort_order',
    ];

    public function pricingModel(): BelongsTo
    {
        return $this->belongsTo(PricingModel::class);
    }

    public function statePath(): string
    {
        if (in_array($this->storageColumn(), self::VENDOR_COLUMNS, true)) {
            return $this->storageColumn();
        }

        return 'extras.prices.'.$this->storageColumn();
    }

    public function storageColumn(): string
    {
        if ($this->key !== 'custom' && in_array($this->key, self::VENDOR_COLUMNS, true)) {
            return $this->key;
        }

        return $this->storage_key ?: $this->package_type;
    }

    public function amountFor(Vendor $vendor): ?float
    {
        if (in_array($this->storageColumn(), self::VENDOR_COLUMNS, true)) {
            $value = $vendor->{$this->storageColumn()};

            return $value === null || $value === '' ? null : (float) $value;
        }

        $value = data_get($vendor->extras, 'prices.'.$this->storageColumn());

        return $value === null || $value === '' ? null : (float) $value;
    }
}
