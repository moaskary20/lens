<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CancellationPolicy extends Model
{
    protected $fillable = [
        'actor', 'name', 'min_hours', 'max_hours', 'client_refund_percent',
        'vendor_payout_percent', 'platform_fee_percent', 'vendor_penalty_percent',
        'description', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'client_refund_percent' => 'decimal:2',
            'vendor_payout_percent' => 'decimal:2',
            'platform_fee_percent' => 'decimal:2',
            'vendor_penalty_percent' => 'decimal:2',
        ];
    }

    public static function match(string $actor, int $hoursNotice): ?self
    {
        return static::query()
            ->where('actor', $actor)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->first(function (self $policy) use ($hoursNotice): bool {
                $minOk = $policy->min_hours === null || $hoursNotice >= $policy->min_hours;
                $maxOk = $policy->max_hours === null || $hoursNotice < $policy->max_hours;

                return $minOk && $maxOk;
            });
    }
}
