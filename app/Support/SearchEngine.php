<?php

namespace App\Support;

use App\Models\FilterGroup;
use App\Models\RecommendationRule;
use App\Models\Setting;

class SearchEngine
{
    /**
     * @return array<string, mixed>
     */
    public static function settings(): array
    {
        return array_merge(self::defaults(), Setting::groupValues('search'));
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'ai_enabled' => true,
            'voice_input_enabled' => true,
            'ai_prompt' => 'What will you create today?',
            'ai_system_prompt' => 'You are the Lens booking assistant. Talk to the client in their language (Arabic or English). Collect location (Egyptian governorate), a shoot date, and budget if missing — one question at a time, in that order. Then recommend real vendors from the catalog only. Never invent names or prices.',
            'gemini_model' => 'gemini-2.5-flash',
            'gemini_api_key' => '',
            'moodboard_mode' => 'after_payment',
            'weight_category' => 20,
            'weight_vendor_type' => 20,
            'weight_location' => 15,
            'weight_availability' => 15,
            'weight_tags' => 15,
            'weight_rating' => 10,
            'weight_completed_sessions' => 5,
            'weight_featured' => 8,
            'weight_badges' => 5,
            'recommend_budget_padding' => 0.25,
            'geo_radii' => '5,10,25',
            'map_enabled' => true,
            'availability_today' => true,
            'availability_weekend' => true,
            'availability_specific' => true,
            'budget_session_slider' => true,
            'budget_package_filter' => true,
            'top_rated_threshold' => 4.8,
            'rank_verified' => true,
            'rank_fast_replies' => true,
            'rank_completed_sessions' => true,
            'rank_reviews' => true,
            'rank_featured' => true,
            'rank_badges' => true,
            'primary_category' => true,
            'primary_creator_type' => true,
            'primary_location' => true,
            'primary_availability' => true,
            'primary_price' => true,
        ];
    }

    /**
     * Config the future app/API should consume.
     *
     * @return array<string, mixed>
     */
    public static function blueprint(): array
    {
        $settings = self::settings();
        $reputation = Reputation::settings();

        if (! $reputation['ranking_uses_ratings']) {
            $settings['weight_rating'] = 0;
            $settings['rank_reviews'] = false;
        }

        return [
            'settings' => $settings,
            'reputation' => $reputation,
            'features' => [
                'filters' => Feature::enabled('filters'),
                'ai_assistant' => Feature::enabled('ai_assistant') && ($settings['ai_enabled'] ?? false),
                'moodboard' => Feature::enabled('moodboard'),
                'maps' => Feature::enabled('maps') && ($settings['map_enabled'] ?? false),
                'smart_recommendations' => Feature::enabled('smart_recommendations'),
            ],
            'groups' => FilterGroup::query()
                ->where('is_active', true)
                ->with(['options' => fn ($query) => $query->where('is_active', true)])
                ->orderBy('sort_order')
                ->get(),
            'recommendation_rules' => RecommendationRule::query()
                ->where('is_active', true)
                ->with('suggestedVendorType')
                ->orderBy('sort_order')
                ->get(),
            'catalog' => self::appFilters(),
        ];
    }

    /**
     * Filter groups the mobile app renders (Filter Models and shared facets).
     *
     * @return list<array<string, mixed>>
     */
    public static function appFilters(): array
    {
        return FilterGroup::query()
            ->where('is_active', true)
            ->with(['options' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get()
            ->map(fn (FilterGroup $group): array => [
                'slug' => $group->slug,
                'name' => $group->name,
                'scope' => $group->scope,
                'input_type' => $group->input_type,
                'vendor_type_slugs' => $group->vendor_type_slugs ?? [],
                'options' => $group->options->map(fn ($option): array => [
                    'slug' => $option->slug,
                    'label' => $option->name_en,
                    'label_ar' => $option->name_ar,
                    'unit' => $option->unit,
                    'min' => $option->min_value !== null ? (float) $option->min_value : null,
                    'max' => $option->max_value !== null ? (float) $option->max_value : null,
                ])->values()->all(),
            ])
            ->values()
            ->all();
    }
}
