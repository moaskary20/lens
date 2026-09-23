<?php

namespace App\Support;

use App\Models\Badge;
use App\Models\Review;
use App\Models\Vendor;

class VendorMetrics
{
    public static function completionRate(Vendor $vendor): float
    {
        if ($vendor->booked_sessions <= 0) {
            return $vendor->completed_sessions > 0 ? 1.0 : 0.0;
        }

        return $vendor->completed_sessions / $vendor->booked_sessions;
    }

    public static function acceptanceRate(Vendor $vendor): float
    {
        $decided = $vendor->accepted_sessions + $vendor->rejected_sessions;

        if ($decided <= 0) {
            return 0.0;
        }

        return $vendor->accepted_sessions / $decided;
    }

    public static function percent(float $rate): string
    {
        return number_format($rate * 100, 0).'%';
    }

    /**
     * @return array<string, float|int>
     */
    public static function platform(): array
    {
        $booked = (int) Vendor::query()->sum('booked_sessions');
        $completed = (int) Vendor::query()->sum('completed_sessions');
        $accepted = (int) Vendor::query()->sum('accepted_sessions');
        $rejected = (int) Vendor::query()->sum('rejected_sessions');
        $failed = (int) Vendor::query()->sum('failed_sessions');

        return [
            'booked' => $booked,
            'completed' => $completed,
            'accepted' => $accepted,
            'rejected' => $rejected,
            'failed' => $failed,
            'penalty_total' => (float) Vendor::query()->sum('penalty_total'),
            'completion_rate' => $booked > 0 ? $completed / $booked : 0.0,
            'acceptance_rate' => ($accepted + $rejected) > 0 ? $accepted / ($accepted + $rejected) : 0.0,
            'rating_avg' => round((float) Review::query()->where('is_visible', true)->avg('rating'), 2),
            'review_count' => (int) Review::query()->where('is_visible', true)->count(),
            'featured' => (int) Vendor::query()->where('is_featured', true)->count(),
            'top_rated' => (int) (Badge::query()->where('slug', 'top-rated')->first()?->vendors()->count() ?? 0),
            'popular' => (int) (Badge::query()->where('slug', 'popular')->first()?->vendors()->count() ?? 0),
        ];
    }
}
