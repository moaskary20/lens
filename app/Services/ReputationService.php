<?php

namespace App\Services;

use App\Models\Badge;
use App\Models\Vendor;
use App\Support\Feature;
use App\Support\Reputation;
use App\Support\VendorMetrics;

class ReputationService
{
    public function bump(Vendor $vendor, string $metric, int $by = 1): void
    {
        if (! in_array($metric, ['booked_sessions', 'completed_sessions', 'accepted_sessions', 'rejected_sessions', 'failed_sessions'], true)) {
            return;
        }

        $vendor->increment($metric, $by);
        $this->refresh($vendor->fresh());
    }

    public function addPenalty(Vendor $vendor, float $amount): void
    {
        if ($amount <= 0) {
            return;
        }

        $vendor->increment('penalty_total', round($amount, 2));
        $this->refresh($vendor->fresh());
    }

    public function refresh(Vendor $vendor): Vendor
    {
        $avg = (float) $vendor->reviews()->where('is_visible', true)->avg('rating');
        $count = $vendor->reviews()->where('is_visible', true)->count();

        $vendor->update([
            'rating_avg' => round($avg, 2),
            'rating_count' => $count,
        ]);

        if (Feature::enabled('badges') && Reputation::settings()['auto_award_badges']) {
            $this->syncBadges($vendor->fresh());
        }

        return $vendor->fresh(['badges']);
    }

    public function refreshAll(): int
    {
        $count = 0;

        Vendor::query()->orderBy('id')->each(function (Vendor $vendor) use (&$count): void {
            $this->refresh($vendor);
            $count++;
        });

        return $count;
    }

    public function syncBadges(Vendor $vendor): void
    {
        $settings = Reputation::settings();
        $ids = [];
        $completion = VendorMetrics::completionRate($vendor);

        $topRated = Badge::query()->where('slug', 'top-rated')->where('is_active', true)->first();
        if ($topRated
            && $vendor->rating_avg >= $settings['top_rated_min']
            && $vendor->rating_count >= $settings['top_rated_min_reviews']) {
            $ids[] = $topRated->id;
        }

        $popular = Badge::query()->where('slug', 'popular')->where('is_active', true)->first();
        if ($popular
            && $vendor->completed_sessions >= $settings['popular_min_completed']
            && $completion >= (float) $settings['popular_min_completion_rate']
            && $vendor->rating_avg >= $settings['popular_min_rating']) {
            $ids[] = $popular->id;
        }

        $fast = Badge::query()->where('slug', 'fast-replies')->where('is_active', true)->first();
        if ($fast && $vendor->response_minutes && $vendor->response_minutes <= 30) {
            $ids[] = $fast->id;
        }

        $automatic = Badge::query()->where('is_automatic', true)->pluck('id');
        $manual = $vendor->badges()->whereNotIn('badges.id', $automatic)->pluck('badges.id');
        $vendor->badges()->sync($manual->merge($ids)->unique()->all());

        if ($settings['auto_feature_top_vendors']) {
            $vendor->update(['is_featured' => $vendor->badges()->whereIn('slug', ['top-rated', 'popular'])->exists()]);
        }
    }
}
