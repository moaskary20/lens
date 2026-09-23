<?php

namespace App\Support;

use App\Models\Setting;

class Reputation
{
    /**
     * @return array<string, mixed>
     */
    public static function settings(): array
    {
        return array_merge([
            'reviews_only_after_approval' => true,
            'top_rated_min' => 4.8,
            'top_rated_min_reviews' => 3,
            'popular_min_completed' => 20,
            'popular_min_completion_rate' => 0.8,
            'popular_min_rating' => 4.5,
            'auto_award_badges' => true,
            'auto_feature_top_vendors' => true,
            'ranking_uses_ratings' => true,
        ], Setting::groupValues('reputation'));
    }
}
