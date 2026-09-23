<?php

namespace App\Models;

use App\Support\StorageQuota;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Portfolio extends Model
{
    protected $fillable = [
        'vendor_id', 'type', 'path', 'title', 'description', 'completed_on',
        'external_url', 'is_featured', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Portfolio $item): void {
            if (! filled($item->path) || $item->type === 'link') {
                return;
            }

            $vendor = $item->vendor ?: Vendor::query()->with(['vendorType', 'portfolios'])->find($item->vendor_id);
            if (! $vendor) {
                return;
            }

            $vendor->loadMissing(['vendorType', 'portfolios']);
            StorageQuota::assertPortfolioFits($vendor, StorageQuota::fileBytes($item->path), $item->exists ? $item->id : null);
        });
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
