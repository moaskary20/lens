<?php

namespace App\Support;

class BookProjectCatalog
{
    public static function typeLabel(string $vendorType): string
    {
        return match ($vendorType) {
            'photographer' => 'Shoot type',
            'videographer' => 'Video type',
            'reels' => 'Platform',
            'studio' => 'Studio use',
            'model' => 'Campaign type',
            'ugc' => 'Content niche',
            'food_stylist' => 'Styling expertise',
            default => 'Project type',
        };
    }

    /**
     * @return list<string>
     */
    public static function typeOptions(string $vendorType): array
    {
        return match ($vendorType) {
            'photographer' => ['Wedding', 'Product', 'Editorial', 'Corporate', 'Food & drinks', 'Event', 'Personal'],
            'videographer' => ['Wedding film', 'Brand film', 'Event recap', 'Documentary', 'Social ad'],
            'reels' => ['Instagram Reels', 'TikTok', 'Snapchat', 'YouTube Shorts'],
            'studio' => ['Photo session', 'Video shoot', 'Podcast', 'Event / launch', 'Cyclorama'],
            'model' => ['Fashion', 'Commercial', 'Lifestyle', 'Fitness', 'Acting'],
            'ugc' => ['Beauty', 'Fashion', 'Food & beverages', 'Travel', 'Lifestyle', 'Unboxing'],
            'food_stylist' => ['Recipe development', 'Food styling', 'Food photography', 'Props & surfaces'],
            default => ['Brand Campaign', 'Product', 'Editorial', 'Social Content', 'Event', 'Personal'],
        };
    }

    /**
     * @return list<array{key: string, label: string, options: list<string>, required: bool}>
     */
    public static function extraFields(string $vendorType): array
    {
        return match ($vendorType) {
            'photographer' => [
                ['key' => 'style', 'label' => 'Visual style', 'options' => ['Natural light', 'Studio strobes', 'Lifestyle', 'Editorial dark'], 'required' => true],
                ['key' => 'deliverables', 'label' => 'Deliverables', 'options' => ['25 edited photos', '50 edited photos', 'Full gallery', 'Same-day preview'], 'required' => true],
            ],
            'videographer' => [
                ['key' => 'runtime', 'label' => 'Runtime', 'options' => ['30–60 seconds', '1–3 minutes', '3–8 minutes', 'Full recap'], 'required' => true],
                ['key' => 'delivery_format', 'label' => 'Delivery format', 'options' => ['4K horizontal', 'Vertical 9:16', 'ProRes', 'Social cutdowns'], 'required' => true],
            ],
            'reels' => [
                ['key' => 'length', 'label' => 'Clip length', 'options' => ['15 seconds', '30 seconds', '60 seconds', 'Series of 3'], 'required' => true],
                ['key' => 'concept', 'label' => 'Concept', 'options' => ['Talking head', 'B-roll story', 'Product demo', 'Trend audio'], 'required' => true],
            ],
            'studio' => [
                ['key' => 'set', 'label' => 'Set / room', 'options' => ['Cyclorama', 'Kitchen', 'Bedroom', 'Podcast room', 'Industrial brick'], 'required' => true],
                ['key' => 'hours', 'label' => 'Hours needed', 'options' => ['2 hours', '4 hours', '6 hours', 'Full day'], 'required' => true],
            ],
            'model' => [
                ['key' => 'look', 'label' => 'Look', 'options' => ['Commercial clean', 'Fashion editorial', 'Casual lifestyle', 'Fitness'], 'required' => true],
                ['key' => 'wardrobe', 'label' => 'Wardrobe', 'options' => ['Talent brings looks', 'Client provides', 'Mixed'], 'required' => false],
            ],
            'ugc' => [
                ['key' => 'platforms', 'label' => 'Posting platforms', 'options' => ['TikTok', 'Instagram', 'Both', 'Client will post'], 'required' => true],
                ['key' => 'content_type', 'label' => 'Content type', 'options' => ['Talking review', 'Unboxing', 'Lifestyle usage', 'Recipe / how-to'], 'required' => true],
            ],
            'food_stylist' => [
                ['key' => 'cuisine', 'label' => 'Cuisine / food type', 'options' => ['Egyptian', 'Levantine', 'Pastry', 'Beverages', 'Mixed menu'], 'required' => true],
                ['key' => 'dishes', 'label' => 'Number of dishes', 'options' => ['1–3 dishes', '4–6 dishes', 'Full menu'], 'required' => true],
            ],
            default => [],
        };
    }

    /**
     * @return list<string>
     */
    public static function timeSlots(): array
    {
        return ['10:00 AM', '12:00 PM', '2:00 PM', '4:00 PM', '6:00 PM'];
    }
}
