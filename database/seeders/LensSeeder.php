<?php

namespace Database\Seeders;

use App\Models\AppScreen;
use App\Models\Badge;
use App\Models\Booking;
use App\Models\CancellationPolicy;
use App\Models\Category;
use App\Models\City;
use App\Models\CmsPage;
use App\Models\Conversation;
use App\Models\Coupon;
use App\Models\Dispute;
use App\Models\EscrowTransaction;
use App\Models\Favorite;
use App\Models\FilterGroup;
use App\Models\FilterTag;
use App\Models\Message;
use App\Models\Payout;
use App\Models\Portfolio;
use App\Models\PricingModel;
use App\Models\RecommendationRule;
use App\Models\Review;
use App\Models\Setting;
use App\Support\Roles;
use App\Support\SearchEngine;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorAvailability;
use App\Models\VendorTravelRate;
use App\Models\VendorType;
use App\Support\Egypt;
use App\Support\Feature;
use App\Support\LensNotifier;
use App\Support\VendorPhotos;
use App\Services\WalletService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class LensSeeder extends Seeder
{
    public function run(): void
    {
        LensNotifier::mute();

        try {
            $this->seedPlatform();
        } finally {
            LensNotifier::unmute();
        }

        $this->seedClientInbox();
    }

    protected function seedPlatform(): void
    {
        Setting::setGroupValues('features', Feature::defaults());
        Setting::setGroupValues('notifications', LensNotifier::defaults());
        foreach (Roles::defaults() as $role => $capabilities) {
            Setting::setValue("roles.{$role}", $capabilities);
        }
        Setting::setGroupValues('finance', [
            'currency' => 'EGP',
            'client_fee_percent' => 10,
            'vendor_commission_percent' => 20,
            'tax_percent' => 0,
            'hold_full_amount' => true,
            'keep_hold_during_revisions' => true,
            'release_requires_deliverables' => true,
            'dispute_client_percent' => 80,
            'dispute_vendor_percent' => 10,
            'dispute_platform_percent' => 10,
        ]);
        Setting::setGroupValues('search', SearchEngine::defaults());
        Setting::setGroupValues('delivery', [
            'watermark_enabled' => true,
            'watermark_text' => 'Lens Protected',
            'anti_screenshot' => true,
            'block_recording' => true,
            'block_download_until_approval' => true,
            'preview_overlay' => true,
            'revision_opens_chat' => true,
        ]);
        Setting::setGroupValues('reputation', [
            'reviews_only_after_approval' => true,
            'top_rated_min' => 4.8,
            'top_rated_min_reviews' => 3,
            'popular_min_completed' => 20,
            'popular_min_completion_rate' => 0.8,
            'popular_min_rating' => 4.5,
            'auto_award_badges' => true,
            'auto_feature_top_vendors' => true,
            'ranking_uses_ratings' => true,
        ]);
        Setting::setGroupValues('platform', [
            'app_name' => 'Lens',
            'support_email' => 'support@lens.app',
            'watermark_text' => 'Lens Protected',
            'ai_prompt' => 'What will you create today?',
            'allow_guest' => true,
            'maintenance_mode' => false,
            'about' => 'A multi-vendor marketplace connecting clients with photographers, studios, and creators through escrow-backed bookings and protected delivery.',
            'default_portfolio_quota_mb' => 500,
            'portfolio_quota_mb' => [
                'photographer' => 500,
                'videographer' => 500,
                'reels' => 500,
                'studio' => 500,
                'model' => 500,
                'ugc' => 500,
                'food_stylist' => 500,
            ],
            'client_project_quota_mb' => 2048,
            'client_project_retention_days' => 7,
        ]);

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@lens.app'],
            [
                'name' => 'Lens Admin',
                'phone' => '0500000001',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'is_active' => true,
                'locale' => 'en',
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'supervisor@lens.app'],
            [
                'name' => 'Quality Supervisor',
                'phone' => '0500000002',
                'password' => Hash::make('password'),
                'role' => 'supervisor',
                'is_active' => true,
                'locale' => 'en',
            ],
        );

        foreach (Egypt::governorates() as $i => $city) {
            City::query()->updateOrCreate(['name_en' => $city['name_en']], [...$city, 'sort_order' => $i, 'is_active' => true]);
        }

        $egyptianIds = City::query()->whereIn('name_en', Egypt::names())->pluck('id');
        $cairoFallback = City::query()->where('name_en', 'Cairo')->value('id');
        User::query()->whereNotNull('city_id')->whereNotIn('city_id', $egyptianIds)->update(['city_id' => $cairoFallback]);
        Vendor::query()->whereNotNull('city_id')->whereNotIn('city_id', $egyptianIds)->update(['city_id' => $cairoFallback]);
        Booking::query()->whereNotNull('city_id')->whereNotIn('city_id', $egyptianIds)->update(['city_id' => $cairoFallback]);
        City::query()->whereNotIn('name_en', Egypt::names())->delete();

        $halfFull = PricingModel::query()->updateOrCreate(
            ['slug' => 'half_full_day'],
            [
                'name_en' => 'Half day / full day',
                'name_ar' => 'نصف يوم / يوم كامل',
                'description' => 'Vendor sets a 6-hour half-day price and a 12-hour full-day price.',
                'is_active' => true,
                'sort_order' => 0,
            ],
        );
        $halfFull->fields()->updateOrCreate(['package_type' => 'half_day'], [
            'key' => 'half_day_price',
            'storage_key' => 'half_day_price',
            'label' => 'Half-day price (6 hours)',
            'unit' => 'session',
            'duration_hours' => 6,
            'helper_text' => 'Your rate for a 6-hour booking.',
            'sort_order' => 0,
        ]);
        $halfFull->fields()->updateOrCreate(['package_type' => 'full_day'], [
            'key' => 'full_day_price',
            'storage_key' => 'full_day_price',
            'label' => 'Full-day price (12 hours)',
            'unit' => 'session',
            'duration_hours' => 12,
            'helper_text' => 'Your rate for a 12-hour booking.',
            'sort_order' => 1,
        ]);

        $hourly = PricingModel::query()->updateOrCreate(
            ['slug' => 'hourly'],
            [
                'name_en' => 'Per hour per location',
                'name_ar' => 'بالساعة لكل موقع',
                'description' => 'Vendor sets an hourly rate for one studio location / room.',
                'is_active' => true,
                'sort_order' => 1,
            ],
        );
        $hourly->fields()->updateOrCreate(['package_type' => 'hourly'], [
            'key' => 'hourly_price',
            'storage_key' => 'hourly_price',
            'label' => 'Price per hour per location',
            'unit' => 'hour',
            'duration_hours' => 1,
            'helper_text' => 'Charged per booked hour for one location.',
            'sort_order' => 0,
        ]);

        $perVideo = PricingModel::query()->updateOrCreate(
            ['slug' => 'per_video'],
            [
                'name_en' => 'Per video',
                'name_ar' => 'لكل فيديو',
                'description' => 'Vendor sets the price for one delivered video.',
                'is_active' => true,
                'sort_order' => 2,
            ],
        );
        $perVideo->fields()->updateOrCreate(['package_type' => 'per_video'], [
            'key' => 'per_video_price',
            'storage_key' => 'per_video_price',
            'label' => 'Price per video',
            'unit' => 'video',
            'duration_hours' => null,
            'helper_text' => 'Your rate for one UGC / delivered video.',
            'sort_order' => 0,
        ]);

        $types = [
            ['slug' => 'photographer', 'name_ar' => 'مصور', 'name_en' => 'Photographer', 'pricing_model' => 'half_full_day', 'pricing_model_id' => $halfFull->id, 'escrow_on_checkin' => false],
            ['slug' => 'videographer', 'name_ar' => 'مصور فيديو', 'name_en' => 'Videographer', 'pricing_model' => 'half_full_day', 'pricing_model_id' => $halfFull->id, 'escrow_on_checkin' => false],
            ['slug' => 'reels', 'name_ar' => 'صانع ريلز', 'name_en' => 'Mobile Reels Creator', 'pricing_model' => 'half_full_day', 'pricing_model_id' => $halfFull->id, 'escrow_on_checkin' => false],
            ['slug' => 'model', 'name_ar' => 'موديل', 'name_en' => 'Model', 'pricing_model' => 'half_full_day', 'pricing_model_id' => $halfFull->id, 'escrow_on_checkin' => true],
            ['slug' => 'studio', 'name_ar' => 'استوديو', 'name_en' => 'Studio', 'pricing_model' => 'hourly', 'pricing_model_id' => $hourly->id, 'escrow_on_checkin' => true],
            ['slug' => 'ugc', 'name_ar' => 'صانع UGC', 'name_en' => 'UGC Creator', 'pricing_model' => 'per_video', 'pricing_model_id' => $perVideo->id, 'escrow_on_checkin' => false],
            ['slug' => 'food_stylist', 'name_ar' => 'منسق طعام', 'name_en' => 'Food Stylist', 'pricing_model' => 'half_full_day', 'pricing_model_id' => $halfFull->id, 'escrow_on_checkin' => false],
        ];

        foreach ($types as $i => $type) {
            VendorType::query()->updateOrCreate(['slug' => $type['slug']], [...$type, 'is_active' => true, 'sort_order' => $i]);
        }

        $categories = [
            ['slug' => 'fnb', 'name_ar' => 'طعام ومشروبات', 'name_en' => 'Food & Drinks'],
            ['slug' => 'wedding', 'name_ar' => 'أعراس ومناسبات', 'name_en' => 'Weddings & Parties'],
            ['slug' => 'events', 'name_ar' => 'فعاليات', 'name_en' => 'Events'],
            ['slug' => 'product', 'name_ar' => 'منتجات وتجارة إلكترونية', 'name_en' => 'Products & E-Commerce'],
            ['slug' => 'photosession', 'name_ar' => 'جلسة تصوير شخصية', 'name_en' => 'Personal Photoshoot'],
            ['slug' => 'corporate', 'name_ar' => 'شركات وأعمال', 'name_en' => 'Corporate & Business'],
        ];

        foreach ($categories as $i => $category) {
            Category::query()->updateOrCreate(['slug' => $category['slug']], [...$category, 'is_active' => true, 'sort_order' => $i]);
        }

        $groups = [
            ['slug' => 'project_type', 'name' => 'Type of project', 'scope' => 'all', 'facet_level' => 'primary', 'input_type' => 'checkbox', 'vendor_type_slugs' => [], 'is_system' => false, 'description' => 'Primary category chips for every search.'],
            ['slug' => 'location', 'name' => 'Location & distance', 'scope' => 'all', 'facet_level' => 'primary', 'input_type' => 'geo', 'vendor_type_slugs' => [], 'is_system' => true, 'description' => 'City / area plus 5 / 10 / 25 km radius and mini-map.'],
            ['slug' => 'availability', 'name' => 'Date & time', 'scope' => 'all', 'facet_level' => 'primary', 'input_type' => 'date', 'vendor_type_slugs' => [], 'is_system' => true, 'description' => 'Today, weekend, or a specific slot matched to vendor calendars.'],
            ['slug' => 'budget', 'name' => 'Budget', 'scope' => 'all', 'facet_level' => 'primary', 'input_type' => 'budget', 'vendor_type_slugs' => [], 'is_system' => true, 'description' => 'Session min/max slider and full-package prices.'],
            ['slug' => 'rating', 'name' => 'Rating & verification', 'scope' => 'all', 'facet_level' => 'quick', 'input_type' => 'rating', 'vendor_type_slugs' => [], 'is_system' => false, 'description' => 'Top rated, verified, fast replies, completed sessions, reviews.'],
            ['slug' => 'camera', 'name' => 'Camera type', 'scope' => 'photographer', 'facet_level' => 'secondary', 'input_type' => 'checkbox', 'vendor_type_slugs' => ['photographer']],
            ['slug' => 'lenses', 'name' => 'Lenses available', 'scope' => 'photographer', 'facet_level' => 'secondary', 'input_type' => 'checkbox', 'vendor_type_slugs' => ['photographer']],
            ['slug' => 'lighting', 'name' => 'Lighting gear', 'scope' => 'photographer', 'facet_level' => 'secondary', 'input_type' => 'checkbox', 'vendor_type_slugs' => ['photographer']],
            ['slug' => 'photo_extras', 'name' => 'Photo extras', 'scope' => 'photographer', 'facet_level' => 'secondary', 'input_type' => 'checkbox', 'vendor_type_slugs' => ['photographer']],
            ['slug' => 'video_quality', 'name' => 'Video quality & formats', 'scope' => 'videographer_reels', 'facet_level' => 'secondary', 'input_type' => 'checkbox', 'vendor_type_slugs' => ['videographer', 'reels']],
            ['slug' => 'video_gear', 'name' => 'Video gear', 'scope' => 'videographer_reels', 'facet_level' => 'secondary', 'input_type' => 'checkbox', 'vendor_type_slugs' => ['videographer', 'reels']],
            ['slug' => 'reels', 'name' => 'Mobile reel creators', 'scope' => 'videographer_reels', 'facet_level' => 'secondary', 'input_type' => 'checkbox', 'vendor_type_slugs' => ['reels']],
            ['slug' => 'studio_type', 'name' => 'Studio Type', 'scope' => 'studio', 'facet_level' => 'primary', 'input_type' => 'checkbox', 'vendor_type_slugs' => ['studio'], 'description' => 'Photo, video, podcast, cyclorama, and other studio kinds.'],
            ['slug' => 'studio_areas', 'name' => 'Studio areas', 'scope' => 'studio', 'facet_level' => 'primary', 'input_type' => 'checkbox', 'vendor_type_slugs' => ['studio'], 'description' => 'Cairo neighborhoods shown on Filter Studios.'],
            ['slug' => 'studio_hourly', 'name' => 'Price Range (EGP/hour)', 'scope' => 'studio', 'facet_level' => 'secondary', 'input_type' => 'range', 'vendor_type_slugs' => ['studio'], 'description' => 'Hourly slider from 100 EGP to 2,000+.'],
            ['slug' => 'studio_size', 'name' => 'Studio Size (m²)', 'scope' => 'studio', 'facet_level' => 'secondary', 'input_type' => 'range', 'vendor_type_slugs' => ['studio'], 'description' => 'Floor area from under 50 m² to 1,000+.'],
            ['slug' => 'studio_features', 'name' => 'Features & Equipment', 'scope' => 'studio', 'facet_level' => 'primary', 'input_type' => 'checkbox', 'vendor_type_slugs' => ['studio'], 'description' => 'Lighting, cyclorama, kitchen, parking, and other studio amenities.'],
            ['slug' => 'studio_sets', 'name' => 'Studio sets & decoration', 'scope' => 'studio', 'facet_level' => 'secondary', 'input_type' => 'checkbox', 'vendor_type_slugs' => ['studio']],
            ['slug' => 'studio_amenities', 'name' => 'Studio amenities', 'scope' => 'studio', 'facet_level' => 'secondary', 'input_type' => 'checkbox', 'vendor_type_slugs' => ['studio']],
            ['slug' => 'studio_equipment', 'name' => 'Equipment included', 'scope' => 'studio', 'facet_level' => 'secondary', 'input_type' => 'checkbox', 'vendor_type_slugs' => ['studio']],
            ['slug' => 'search_cities', 'name' => 'Location', 'scope' => 'all', 'facet_level' => 'primary', 'input_type' => 'checkbox', 'vendor_type_slugs' => [], 'is_system' => false, 'description' => 'Egyptian cities and areas shown as chips in the mobile filter.'],
            ['slug' => 'model_identity', 'name' => 'Gender', 'scope' => 'model', 'facet_level' => 'primary', 'input_type' => 'segment', 'vendor_type_slugs' => ['model'], 'description' => 'Men / Women / Kids tabs on Filter Models.'],
            ['slug' => 'model_category', 'name' => 'Category', 'scope' => 'model', 'facet_level' => 'primary', 'input_type' => 'checkbox', 'vendor_type_slugs' => ['model'], 'description' => 'Fashion, commercial, acting, and other model work types.'],
            ['slug' => 'model_age', 'name' => 'Age Range', 'scope' => 'model', 'facet_level' => 'secondary', 'input_type' => 'range', 'vendor_type_slugs' => ['model'], 'description' => 'Under 18 through 50+ with an 18–50 year slider.'],
            ['slug' => 'model_height', 'name' => 'Height (cm)', 'scope' => 'model', 'facet_level' => 'secondary', 'input_type' => 'range', 'vendor_type_slugs' => ['model'], 'description' => 'Under 160 through 200+ cm with a 160–200 slider.'],
            ['slug' => 'model_tops', 'name' => 'T-Shirt / Tops', 'scope' => 'model', 'facet_level' => 'secondary', 'input_type' => 'checkbox', 'vendor_type_slugs' => ['model']],
            ['slug' => 'model_pants', 'name' => 'Pants / Bottoms (Waist)', 'scope' => 'model', 'facet_level' => 'secondary', 'input_type' => 'checkbox', 'vendor_type_slugs' => ['model']],
            ['slug' => 'model_shoes', 'name' => 'Shoes (EU)', 'scope' => 'model', 'facet_level' => 'secondary', 'input_type' => 'checkbox', 'vendor_type_slugs' => ['model']],
            ['slug' => 'model_sizes', 'name' => 'Size (Clothes & Shoes)', 'scope' => 'model', 'facet_level' => 'secondary', 'input_type' => 'checkbox', 'vendor_type_slugs' => ['model'], 'is_system' => false, 'description' => 'Parent section for tops, waist, and EU shoes.'],
            ['slug' => 'model_experience', 'name' => 'Work experience', 'scope' => 'model', 'facet_level' => 'secondary', 'input_type' => 'checkbox', 'vendor_type_slugs' => ['model'], 'is_system' => false],
            ['slug' => 'ugc_niche', 'name' => 'Category / Niche', 'scope' => 'ugc_food', 'facet_level' => 'primary', 'input_type' => 'checkbox', 'vendor_type_slugs' => ['ugc'], 'description' => 'Beauty, fashion, food, travel, and other UGC niches.'],
            ['slug' => 'ugc_cities', 'name' => 'City (Egypt)', 'scope' => 'ugc_food', 'facet_level' => 'primary', 'input_type' => 'checkbox', 'vendor_type_slugs' => ['ugc'], 'description' => 'Egyptian cities on Filter UGC Creators.'],
            ['slug' => 'ugc_accent', 'name' => 'Accent', 'scope' => 'ugc_food', 'facet_level' => 'secondary', 'input_type' => 'checkbox', 'vendor_type_slugs' => ['ugc']],
            ['slug' => 'ugc_followers', 'name' => 'Followers Count', 'scope' => 'ugc_food', 'facet_level' => 'secondary', 'input_type' => 'range', 'vendor_type_slugs' => ['ugc'], 'description' => 'From under 1K to 1M+ followers.'],
            ['slug' => 'ugc_price', 'name' => 'Price Range (EGP / video)', 'scope' => 'ugc_food', 'facet_level' => 'secondary', 'input_type' => 'range', 'vendor_type_slugs' => ['ugc'], 'description' => 'Per-video slider from 500 EGP to 10,000+.'],
            ['slug' => 'ugc_content', 'name' => 'Content Type', 'scope' => 'ugc_food', 'facet_level' => 'primary', 'input_type' => 'checkbox', 'vendor_type_slugs' => ['ugc']],
            ['slug' => 'ugc_gender', 'name' => 'Gender', 'scope' => 'ugc_food', 'facet_level' => 'secondary', 'input_type' => 'segment', 'vendor_type_slugs' => ['ugc']],
            ['slug' => 'ugc_age', 'name' => 'Age range', 'scope' => 'ugc_food', 'facet_level' => 'secondary', 'input_type' => 'checkbox', 'vendor_type_slugs' => ['ugc']],
            ['slug' => 'ugc_language', 'name' => 'Language', 'scope' => 'ugc_food', 'facet_level' => 'secondary', 'input_type' => 'checkbox', 'vendor_type_slugs' => ['ugc']],
            ['slug' => 'ugc', 'name' => 'UGC creators', 'scope' => 'ugc_food', 'facet_level' => 'secondary', 'input_type' => 'checkbox', 'vendor_type_slugs' => ['ugc']],
            ['slug' => 'food_expertise', 'name' => 'Category / Expertise', 'scope' => 'ugc_food', 'facet_level' => 'primary', 'input_type' => 'checkbox', 'vendor_type_slugs' => ['food_stylist'], 'description' => 'Recipe development, styling, photography, and props.'],
            ['slug' => 'food_cuisine', 'name' => 'Cuisine / Food Type', 'scope' => 'ugc_food', 'facet_level' => 'primary', 'input_type' => 'checkbox', 'vendor_type_slugs' => ['food_stylist']],
            ['slug' => 'food_cities', 'name' => 'Egyptian Cities', 'scope' => 'ugc_food', 'facet_level' => 'primary', 'input_type' => 'checkbox', 'vendor_type_slugs' => ['food_stylist']],
            ['slug' => 'food_price', 'name' => 'Price Range (EGP / video)', 'scope' => 'ugc_food', 'facet_level' => 'secondary', 'input_type' => 'range', 'vendor_type_slugs' => ['food_stylist'], 'description' => 'From 500 EGP to 10,000+ per video.'],
            ['slug' => 'food_content', 'name' => 'Content Type', 'scope' => 'ugc_food', 'facet_level' => 'primary', 'input_type' => 'checkbox', 'vendor_type_slugs' => ['food_stylist']],
            ['slug' => 'food_stylist', 'name' => 'Food stylists', 'scope' => 'ugc_food', 'facet_level' => 'secondary', 'input_type' => 'checkbox', 'vendor_type_slugs' => ['food_stylist']],
        ];

        foreach ($groups as $i => $group) {
            FilterGroup::query()->updateOrCreate(['slug' => $group['slug']], [...$group, 'is_active' => true, 'sort_order' => $i]);
        }

        $filters = [
            ['Food & Drinks', 'طعام ومشروبات', 'fnb', 'project_type', [], ['food', 'fnb', 'restaurant']],
            ['Weddings & Parties', 'أعراس ومناسبات', 'wedding', 'project_type', [], ['wedding', 'party']],
            ['Products & E-Commerce', 'منتجات وتجارة إلكترونية', 'product', 'project_type', [], ['product', 'ecommerce']],
            ['Personal Photoshoot / Portraits', 'جلسة تصوير شخصية', 'photosession', 'project_type', [], ['portrait', 'headshot']],
            ['Events', 'فعاليات', 'events', 'project_type', [], ['event', 'conference']],
            ['Corporate & Business', 'شركات وأعمال', 'corporate', 'project_type', [], ['corporate', 'office']],
            ['Available today', 'متاح اليوم', 'available-today', 'availability', [], ['today']],
            ['Available weekend', 'متاح نهاية الأسبوع', 'available-weekend', 'availability', [], ['weekend']],
            ['Full-frame camera', 'كاميرا فريم كامل', 'full-frame', 'camera', ['photographer'], ['full frame', 'ff']],
            ['Medium format camera', 'كاميرا ميديوم فورمات', 'medium-format', 'camera', ['photographer']],
            ['Crop sensor camera', 'كاميرا كروب سنسور', 'crop-sensor', 'camera', ['photographer']],
            ['Portrait lenses 50/85', 'عدسات بورتريه 50/85', 'portrait-lenses', 'lenses', ['photographer']],
            ['Wide lenses 16-35', 'عدسات واسعة 16-35', 'wide-lenses', 'lenses', ['photographer']],
            ['Macro lenses', 'عدسات ماكرو', 'macro-lenses', 'lenses', ['photographer']],
            ['Zoom / telephoto 70-200', 'عدسات زوم 70-200', 'tele-lenses', 'lenses', ['photographer']],
            ['Studio flash / strobes', 'فلاش استوديو / ستروب', 'strobes', 'lighting', ['photographer']],
            ['Continuous LED light', 'إضاءة LED مستمرة', 'led-continuous', 'lighting', ['photographer']],
            ['Portable battery flashes', 'فلاش محمول', 'speedlight', 'lighting', ['photographer']],
            ['RAW unedited photos', 'صور خام RAW', 'raw-included', 'photo_extras', ['photographer']],
            ['High-end retouching', 'ريتاتوش احترافي', 'retouching', 'photo_extras', ['photographer']],
            ['Fast delivery 24-48h', 'تسليم سريع 24-48', 'fast-delivery', 'photo_extras', ['photographer', 'videographer', 'reels']],
            ['4K quality', 'جودة 4K', '4k', 'video_quality', ['videographer', 'reels']],
            ['Log / ProRes', 'Log / ProRes', 'prores', 'video_quality', ['videographer']],
            ['Slow motion', 'تصوير بطيء', 'slow-motion', 'video_quality', ['videographer']],
            ['Vertical video 9:16', 'فيديو عمودي 9:16', 'vertical-video', 'video_quality', ['videographer', 'reels']],
            ['Gimbal / stabilizer', 'جيمبل / مثبت', 'gimbal', 'video_gear', ['videographer', 'reels']],
            ['Slider / crane', 'سلايدر / كرين', 'slider', 'video_gear', ['videographer']],
            ['Shoots on latest iPhone', 'تصوير بآيفون حديث', 'iphone-pro', 'reels', ['reels']],
            ['Social media edit', 'مونتاج سوشيال', 'social-edit', 'reels', ['reels']],
            ['Same-day delivery', 'تسليم في نفس اليوم', 'same-day', 'reels', ['reels']],
            ['Portable light kit', 'طقم إضاءة محمول', 'portable-light', 'reels', ['reels']],
            ['Bedroom set', 'ديكور غرفة نوم', 'bedroom-set', 'studio_sets', ['studio']],
            ['Full working kitchen', 'مطبخ كامل', 'kitchen-set', 'studio_sets', ['studio']],
            ['Cyclorama wall', 'جدار سيكلوراما', 'cyclorama', 'studio_sets', ['studio']],
            ['Green screen', 'شاشة خضراء', 'green-screen', 'studio_sets', ['studio']],
            ['Industrial brick wall', 'جدار طوب صناعي', 'brick-wall', 'studio_sets', ['studio']],
            ['Podcast room', 'غرفة بودكاست', 'podcast-room', 'studio_sets', ['studio']],
            ['Makeup room', 'غرفة مكياج', 'makeup-room', 'studio_amenities', ['studio']],
            ['Air conditioned', 'تكييف', 'ac', 'studio_amenities', ['studio']],
            ['On-site parking', 'موقف سيارات', 'parking', 'studio_amenities', ['studio']],
            ['Live client monitor', 'شاشة متابعة للعميل', 'client-monitor', 'studio_amenities', ['studio']],
            ['Studio lighting included', 'إضاءة الاستوديو مشمولة', 'studio-lights', 'studio_equipment', ['studio']],
            ['Props & furniture', 'إكسسوارات وأثاث', 'props', 'studio_equipment', ['studio']],
            ['Any', 'أي تقييم', 'rating-any', 'rating', []],
            ['4.0+', '4.0+', 'rating-4-0', 'rating', []],
            ['4.5+', '4.5+', 'rating-4-5', 'rating', []],
            ['Unboxing & reviews', 'فتح علب ومراجعات', 'unboxing', 'ugc', ['ugc']],
            ['Lifestyle & vlogs', 'لايف ستايل وفلوغ', 'lifestyle', 'ugc', ['ugc']],
            ['Voiceover included', 'تعليق صوتي', 'voiceover', 'ugc', ['ugc']],
            ['Face-on-camera presenter', 'ظهور أمام الكاميرا', 'on-camera', 'ugc', ['ugc']],
            ['Recipe development', 'تطوير وصفات', 'recipe-dev', 'food_stylist', ['food_stylist']],
            ['Prop sourcing', 'توفير إكسسوار المائدة', 'prop-sourcing', 'food_stylist', ['food_stylist']],
            ['Food staging', 'تنسيق طعام تجاري', 'food-staging', 'food_stylist', ['food_stylist']],
            ['4.8+', '4.8+', 'top-rated', 'rating', []],
            ['Verified badge', 'شارة موثّق', 'verified-badge', 'rating', []],
            ['Fast replies', 'رد سريع', 'fast-replies', 'rating', []],
            ['Successful sessions', 'جلسات ناجحة', 'successful-sessions', 'rating', []],
            ['Client reviews', 'تقييمات العملاء', 'client-reviews', 'rating', []],
            ['Any Date', 'أي تاريخ', 'availability-any', 'availability', [], ['any date']],
            ['This Week', 'هذا الأسبوع', 'availability-week', 'availability', [], ['this week']],
            ['This Month', 'هذا الشهر', 'availability-month', 'availability', [], ['this month']],
            ['Custom Date', 'تاريخ مخصص', 'availability-custom', 'availability', [], ['custom date']],
        ];

        $helpers = [
            'portrait-lenses' => '50mm / 85mm f/1.2 – f/1.4',
            'wide-lenses' => '16–35mm',
            'macro-lenses' => 'Close-up product and jewelry',
            'tele-lenses' => '70–200mm',
            'slow-motion' => '120fps / 240fps',
            'vertical-video' => '9:16 for social media',
            'iphone-pro' => 'iPhone 15/16 Pro',
            'social-edit' => 'Trending audio and transitions',
            'top-rated' => '4.8+ stars',
            'kitchen-set' => 'Full working kitchen',
            'cyclorama' => 'Curved white wall',
            'green-screen' => 'Chroma key',
        ];

        foreach ($filters as $i => $row) {
            [$nameEn, $nameAr, $slug, $group, $typesSlugs] = $row;
            $synonyms = $row[5] ?? [];
            $groupModel = FilterGroup::query()->where('slug', $group)->first();

            FilterTag::query()->updateOrCreate(['slug' => $slug], [
                'filter_group_id' => $groupModel?->id,
                'name_en' => $nameEn,
                'name_ar' => $nameAr,
                'helper_text' => $helpers[$slug] ?? null,
                'group_key' => $group,
                'vendor_type_slugs' => $typesSlugs,
                'synonyms' => $synonyms,
                'is_active' => true,
                'show_in_quick_filters' => in_array($group, ['project_type', 'rating'], true),
                'sort_order' => $i,
            ]);
        }

        $this->seedModelMarketplaceFilters();
        $this->seedStudioMarketplaceFilters();
        $this->seedUgcMarketplaceFilters();
        $this->seedFoodStylistMarketplaceFilters();

        $foodStylistType = VendorType::query()->where('slug', 'food_stylist')->first();
        $studioType = VendorType::query()->where('slug', 'studio')->first();
        $kitchen = FilterTag::query()->where('slug', 'kitchen-set')->first();
        $foodStaging = FilterTag::query()->where('slug', 'food-staging')->first();

        foreach ([
            [
                'name' => 'Food shoot needs a stylist',
                'trigger_type' => 'category',
                'trigger_value' => 'fnb',
                'suggest_vendor_type_id' => $foodStylistType?->id,
                'suggest_filter_tag_ids' => array_filter([$foodStaging?->id]),
                'suggest_message' => 'A food stylist will plate and light the dishes so the final images look commercial.',
            ],
            [
                'name' => 'Food shoot needs a kitchen studio',
                'trigger_type' => 'category',
                'trigger_value' => 'fnb',
                'suggest_vendor_type_id' => $studioType?->id,
                'suggest_filter_tag_ids' => array_filter([$kitchen?->id]),
                'suggest_message' => 'Book a studio with a full working kitchen set.',
            ],
            [
                'name' => 'Keyword: food photo',
                'trigger_type' => 'keyword',
                'trigger_value' => 'food',
                'suggest_vendor_type_id' => $foodStylistType?->id,
                'suggest_filter_tag_ids' => array_filter([$kitchen?->id, $foodStaging?->id]),
                'suggest_message' => 'Pair a photographer with a food stylist and a kitchen studio.',
            ],
        ] as $i => $rule) {
            RecommendationRule::query()->updateOrCreate(['name' => $rule['name']], [...$rule, 'is_active' => true, 'sort_order' => $i]);
        }

        foreach ([
            ['slug' => 'verified', 'name_ar' => 'موثّق', 'name_en' => 'Verified', 'color' => '#FF5A1F', 'is_automatic' => false, 'criteria' => 'Staff verification of identity and portfolio.'],
            ['slug' => 'top-rated', 'name_ar' => 'الأعلى تقييماً', 'name_en' => 'Top Rated', 'color' => '#F79646', 'is_automatic' => true, 'criteria' => 'Average 4.8+ stars from enough approved-session reviews. Boosts search ranking and can unlock featured placement.'],
            ['slug' => 'popular', 'name_ar' => 'الأكثر طلباً', 'name_en' => 'Popular', 'color' => '#B73A0F', 'is_automatic' => true, 'criteria' => 'High completion rate, enough completed sessions, and positive reviews.'],
            ['slug' => 'fast-replies', 'name_ar' => 'رد سريع', 'name_en' => 'Fast Replies', 'color' => '#3D8B5F', 'is_automatic' => true, 'criteria' => 'Average reply time of 30 minutes or less.'],
        ] as $badge) {
            Badge::query()->updateOrCreate(['slug' => $badge['slug']], $badge + ['is_active' => true]);
        }

        $policies = [
            ['actor' => 'client', 'name' => 'Client cancel > 72 hours', 'min_hours' => 72, 'max_hours' => null, 'client_refund_percent' => 100, 'vendor_payout_percent' => 0, 'platform_fee_percent' => 10, 'vendor_penalty_percent' => 0],
            ['actor' => 'client', 'name' => 'Client cancel 72 to 48 hours', 'min_hours' => 48, 'max_hours' => 72, 'client_refund_percent' => 65, 'vendor_payout_percent' => 25, 'platform_fee_percent' => 10, 'vendor_penalty_percent' => 0],
            ['actor' => 'client', 'name' => 'Client cancel 48 to 24 hours', 'min_hours' => 24, 'max_hours' => 48, 'client_refund_percent' => 40, 'vendor_payout_percent' => 50, 'platform_fee_percent' => 10, 'vendor_penalty_percent' => 0],
            ['actor' => 'client', 'name' => 'Client cancel < 24 hours', 'min_hours' => 0, 'max_hours' => 24, 'client_refund_percent' => 0, 'vendor_payout_percent' => 80, 'platform_fee_percent' => 10, 'vendor_penalty_percent' => 0],
            ['actor' => 'vendor', 'name' => 'Same-day vendor cancel / no-show', 'min_hours' => 0, 'max_hours' => 24, 'client_refund_percent' => 100, 'vendor_payout_percent' => 0, 'platform_fee_percent' => 0, 'vendor_penalty_percent' => 50],
            ['actor' => 'vendor', 'name' => 'Vendor cancel > 24 hours', 'min_hours' => 24, 'max_hours' => null, 'client_refund_percent' => 100, 'vendor_payout_percent' => 0, 'platform_fee_percent' => 0, 'vendor_penalty_percent' => 25],
        ];

        foreach ($policies as $i => $policy) {
            CancellationPolicy::query()->updateOrCreate(['name' => $policy['name']], [...$policy, 'is_active' => true, 'sort_order' => $i]);
        }

        foreach ([
            ['slug' => 'intro', 'title' => 'Your lens to creativity', 'body' => 'Discover photographers, studios, and creators near you.', 'cta_label' => 'Get started', 'sort_order' => 1],
            ['slug' => 'join-vendor', 'title' => 'Join as vendor', 'body' => 'Build your portfolio, set availability, and receive secure bookings.', 'cta_label' => 'Join as vendor', 'sort_order' => 2],
            ['slug' => 'join-user', 'title' => 'Join as client', 'body' => 'Book a session and pay with Lens escrow protection.', 'cta_label' => 'Join as client', 'sort_order' => 3],
            ['slug' => 'guest', 'title' => 'Continue as guest', 'body' => 'Browse the marketplace without an account, then book when you are ready.', 'cta_label' => 'Continue as guest', 'sort_order' => 4],
        ] as $screen) {
            AppScreen::query()->updateOrCreate(['slug' => $screen['slug']], $screen + ['is_active' => true]);
        }

        foreach ([
            ['slug' => 'terms', 'title' => 'Terms of Service', 'body' => 'Terms for using Lens for booking, payments, and protected delivery.'],
            ['slug' => 'privacy', 'title' => 'Privacy Policy', 'body' => 'How Lens stores client and vendor data.'],
            ['slug' => 'escrow', 'title' => 'Escrow Policy', 'body' => 'Funds stay held until client approval or check-in, depending on vendor type.'],
        ] as $page) {
            CmsPage::query()->updateOrCreate(['slug' => $page['slug']], $page + ['is_active' => true]);
        }

        $cairo = City::query()->where('name_en', 'Cairo')->first();
        $alexandria = City::query()->where('name_en', 'Alexandria')->first();
        $photographer = VendorType::query()->where('slug', 'photographer')->first();
        $studio = VendorType::query()->where('slug', 'studio')->first();

        $client = User::query()->updateOrCreate(
            ['email' => 'client@lens.app'],
            ['name' => 'Sarah Bennett', 'phone' => '01011112233', 'password' => Hash::make('password'), 'role' => 'client', 'city_id' => $cairo?->id, 'is_active' => true, 'locale' => 'en'],
        );

        $vendorUser = User::query()->updateOrCreate(
            ['email' => 'vendor@lens.app'],
            ['name' => 'Fahad Photography', 'phone' => '01022223344', 'password' => Hash::make('password'), 'role' => 'vendor', 'city_id' => $cairo?->id, 'is_active' => true, 'locale' => 'en'],
        );

        $studioUser = User::query()->updateOrCreate(
            ['email' => 'studio@lens.app'],
            ['name' => 'Noor Studio', 'phone' => '01033334455', 'password' => Hash::make('password'), 'role' => 'vendor', 'city_id' => $alexandria?->id, 'is_active' => true, 'locale' => 'en'],
        );

        $photoVendor = Vendor::query()->updateOrCreate(
            ['user_id' => $vendorUser->id],
            [
                'vendor_type_id' => $photographer?->id,
                'city_id' => $cairo?->id,
                'display_name' => 'Fahad Studio Light',
                'bio' => 'Product and wedding photography with portrait lenses and strobe lighting.',
                'verification_status' => 'verified',
                'is_active' => true,
                'is_featured' => true,
                'accepts_out_of_governorate' => true,
                'default_travel_fee' => 250,
                'profile_photo' => VendorPhotos::portrait('photographer', 1),
                'cover_image' => VendorPhotos::cover('photographer', 0),
                'national_id_image' => 'vendors/ids/fahad-nid.jpg',
                'date_of_birth' => '1992-04-18',
                'profession' => 'Wedding & product photographer',
                'contact_phone' => '01022223344',
                'contact_email' => 'vendor@lens.app',
                'whatsapp' => '01022223344',
                'instagram' => '@fahadstudiolight',
                'payout_method' => 'bank',
                'bank_name' => 'Banque Misr',
                'bank_account_holder' => 'Fahad Photography',
                'bank_account_number' => '2039485761',
                'bank_iban' => 'EG380003000203948576100000000',
                'bank_swift' => 'BMISEGCXXXX',
                'bank_branch' => 'Zamalek',
                'bank_branch_code' => '003',
                'bank_account_type' => 'current',
                'instapay' => 'fahad@banquemisr',
                'transfer_notes' => 'Use InstaPay for same-day payouts.',
                'half_day_price' => 1800,
                'full_day_price' => 3200,
                'specialties' => ['wedding', 'product', 'editorial'],
                'extras' => [
                    'cameras' => ['Canon R5'],
                    'lenses' => ['85mm f/1.2'],
                    'lighting' => ['Godox AD600'],
                ],
                'completed_sessions' => 42,
                'booked_sessions' => 48,
                'accepted_sessions' => 45,
                'rejected_sessions' => 3,
                'failed_sessions' => 1,
                'penalty_total' => 250,
                'rating_avg' => 4.9,
                'rating_count' => 37,
                'response_minutes' => 18,
            ],
        );

        VendorTravelRate::query()->updateOrCreate(
            ['vendor_id' => $photoVendor->id, 'destination_governorate' => 'Alexandria'],
            ['fee' => 450, 'notes' => 'Cairo to Alexandria road transfer'],
        );
        VendorTravelRate::query()->updateOrCreate(
            ['vendor_id' => $photoVendor->id, 'destination_governorate' => 'Giza'],
            ['fee' => 350, 'notes' => 'Giza / 6th of October transfer'],
        );
        VendorTravelRate::query()
            ->where('vendor_id', $photoVendor->id)
            ->whereNotIn('destination_governorate', ['Alexandria', 'Giza'])
            ->delete();

        $studioVendor = Vendor::query()->updateOrCreate(
            ['user_id' => $studioUser->id],
            [
                'vendor_type_id' => $studio?->id,
                'city_id' => $alexandria?->id,
                'display_name' => 'Noor Cyclorama',
                'bio' => 'Studio with a full kitchen, curved white wall, and on-site parking.',
                'verification_status' => 'pending',
                'is_active' => true,
                'hourly_price' => 250,
                'profile_photo' => VendorPhotos::portrait('studio', 0),
                'cover_image' => VendorPhotos::cover('studio', 0),
                'extras' => [
                    'rooms' => ['kitchen', 'bedroom', 'cyclorama'],
                    'props' => ['Dining table', 'Vintage chairs', 'Marble backdrop'],
                ],
                'completed_sessions' => 19,
                'booked_sessions' => 24,
                'accepted_sessions' => 20,
                'rejected_sessions' => 3,
                'failed_sessions' => 1,
                'penalty_total' => 0,
                'rating_avg' => 4.6,
                'rating_count' => 12,
            ],
        );

        VendorAvailability::query()->updateOrCreate(
            ['vendor_id' => $photoVendor->id, 'starts_at' => now()->addDays(4)->setTime(9, 0)],
            ['ends_at' => now()->addDays(4)->setTime(15, 0), 'status' => 'open', 'notes' => 'Half-day product slot'],
        );
        VendorAvailability::query()->updateOrCreate(
            ['vendor_id' => $photoVendor->id, 'starts_at' => now()->addDays(6)->setTime(10, 0)],
            ['ends_at' => now()->addDays(6)->setTime(18, 0), 'status' => 'blocked', 'notes' => 'Personal hold'],
        );
        VendorAvailability::query()->updateOrCreate(
            ['vendor_id' => $studioVendor->id, 'starts_at' => now()->addDays(5)->setTime(10, 0)],
            ['ends_at' => now()->addDays(5)->setTime(14, 0), 'status' => 'open', 'notes' => 'Kitchen + cyclorama hourly block'],
        );

        Portfolio::query()->updateOrCreate(
            ['vendor_id' => $photoVendor->id, 'title' => 'Yasmin Hall wedding'],
            [
                'type' => 'image',
                'path' => 'portfolios/fahad-wedding.jpg',
                'description' => 'Reception and couple portraits with strobe lighting.',
                'completed_on' => '2025',
                'is_featured' => true,
                'sort_order' => 1,
            ],
        );
        Portfolio::query()->updateOrCreate(
            ['vendor_id' => $photoVendor->id, 'title' => 'Fragrance still life'],
            [
                'type' => 'image',
                'path' => 'portfolios/fahad-product.jpg',
                'description' => 'Product grid for a Cairo perfume launch.',
                'completed_on' => '2024',
                'is_featured' => false,
                'sort_order' => 2,
            ],
        );
        Portfolio::query()->updateOrCreate(
            ['vendor_id' => $studioVendor->id, 'title' => 'Cyclorama wall'],
            [
                'type' => 'image',
                'path' => 'portfolios/noor-cyc.jpg',
                'description' => 'Infinity wall ready for e-commerce and lookbooks.',
                'completed_on' => '2025',
                'is_featured' => true,
                'sort_order' => 1,
            ],
        );

        $photoVendor->categories()->sync(Category::query()->whereIn('slug', ['wedding', 'product', 'fnb'])->pluck('id'));
        $photoVendor->filterTags()->sync(FilterTag::query()->whereIn('slug', ['full-frame', 'portrait-lenses', 'strobes', 'retouching'])->pluck('id'));
        $photoVendor->badges()->sync(Badge::query()->whereIn('slug', ['verified', 'top-rated'])->pluck('id'));
        $studioVendor->filterTags()->sync(FilterTag::query()->whereIn('slug', [
            'kitchen-set', 'cyclorama', 'bedroom-set', 'studio-type-photo', 'green-screen',
            'studio-lights', 'studio-area-cairo', 'makeup-room', 'parking',
        ])->pluck('id'));

        $this->seedHomeShowcaseVendors($cairo?->id, $alexandria?->id);

        $category = Category::query()->where('slug', 'wedding')->first();
        $booking = Booking::query()->updateOrCreate(
            ['reference' => 'LN-1001'],
            [
                'client_id' => $client->id,
                'vendor_id' => $photoVendor->id,
                'category_id' => $category?->id,
                'status' => 'delivered',
                'scheduled_at' => now()->addDays(3),
                'duration_hours' => 6,
                'package_type' => 'half_day',
                'location_text' => 'Yasmin Hall, Cairo',
                'client_brief' => 'Wedding recap at Yasmin Hall. Soft light, couple portraits, and 50 edited photos delivered on Lens.',
                'session_price' => 1800,
                'client_fee' => 180,
                'tax_amount' => 0,
                'total_paid' => 1980,
                'vendor_commission' => 360,
                'vendor_net' => 1440,
                'escrow_status' => 'held',
                'payout_status' => 'none',
            ],
        );

        EscrowTransaction::query()->updateOrCreate(
            ['booking_id' => $booking->id, 'type' => 'hold'],
            ['amount' => 1980, 'status' => 'completed', 'processed_by' => $admin->id, 'notes' => 'Funds held at checkout'],
        );

        Booking::query()->updateOrCreate(
            ['reference' => 'LN-1003'],
            [
                'client_id' => $client->id,
                'vendor_id' => $photoVendor->id,
                'category_id' => $category?->id,
                'status' => 'pending',
                'scheduled_at' => now()->addDays(20)->setTime(11, 0),
                'duration_hours' => 6,
                'package_type' => 'half_day',
                'location_text' => 'Zamalek rooftop, Cairo',
                'session_price' => 1800,
                'client_fee' => 180,
                'total_paid' => 1980,
                'vendor_commission' => 360,
                'vendor_net' => 1440,
                'escrow_status' => 'none',
                'payout_status' => 'none',
            ],
        );

        Booking::query()->updateOrCreate(
            ['reference' => 'LN-1002'],
            [
                'client_id' => $client->id,
                'vendor_id' => $studioVendor->id,
                'status' => 'disputed',
                'scheduled_at' => now()->subDay(),
                'package_type' => 'hourly',
                'duration_hours' => 4,
                'session_price' => 1000,
                'client_fee' => 100,
                'total_paid' => 1100,
                'vendor_commission' => 200,
                'vendor_net' => 800,
                'escrow_status' => 'held',
                'location_text' => 'Alexandria',
            ],
        );

        $second = Booking::query()->where('reference', 'LN-1002')->first();

        Dispute::query()->updateOrCreate(
            ['booking_id' => $second->id],
            [
                'opened_by' => $client->id,
                'status' => 'open',
                'kind' => 'dispute',
                'reason' => 'The booked space does not match the portfolio.',
                'client_refund_percent' => 80,
                'vendor_payout_percent' => 10,
                'platform_fee_percent' => 10,
            ],
        );

        $studioChat = Conversation::query()->updateOrCreate(
            ['booking_id' => $second->id],
            [
                'client_id' => $client->id,
                'vendor_id' => $studioVendor->id,
                'last_message_at' => now(),
            ],
        );
        Message::query()->updateOrCreate(
            ['conversation_id' => $studioChat->id, 'body' => 'The cyclorama wall is not the one in the listing photos.'],
            ['sender_id' => $client->id],
        );

        Review::withoutEvents(function () use ($booking, $client, $photoVendor): void {
            Review::query()->updateOrCreate(
                ['booking_id' => $booking->id],
                [
                    'client_id' => $client->id,
                    'vendor_id' => $photoVendor->id,
                    'rating' => 5,
                    'comment' => 'Professional shoot and fast delivery.',
                    'is_visible' => true,
                ],
            );
        });

        $conversation = Conversation::query()->updateOrCreate(
            ['booking_id' => $booking->id],
            [
                'client_id' => $client->id,
                'vendor_id' => $photoVendor->id,
                'last_message_at' => now(),
            ],
        );
        Message::query()->updateOrCreate(
            ['conversation_id' => $conversation->id, 'body' => 'Please confirm the hall access time.'],
            ['sender_id' => $client->id],
        );

        Payout::query()->updateOrCreate(
            ['booking_id' => $booking->id],
            [
                'vendor_id' => $photoVendor->id,
                'amount' => 1440,
                'status' => 'pending',
                'method' => 'bank',
                'notes' => 'Held until the client approves LN-1001.',
            ],
        );

        $wallets = app(WalletService::class);
        foreach (User::query()->get() as $user) {
            $wallets->ensure($user);
        }

        $coupon = Coupon::query()->updateOrCreate(
            ['code' => 'WELCOME200'],
            [
                'label' => 'Welcome wallet credit',
                'campaign' => Coupon::CAMPAIGN_USER,
                'type' => Coupon::TYPE_WALLET,
                'amount' => 200,
                'user_id' => $client->id,
                'max_uses' => 1,
                'max_uses_per_user' => 1,
                'is_active' => true,
                'auto_apply' => false,
            ],
        );
        $coupon->assignedUsers()->sync([$client->id]);

        Coupon::query()->updateOrCreate(
            ['code' => 'SUMMER15'],
            [
                'label' => 'Summer seasonal',
                'campaign' => Coupon::CAMPAIGN_SEASONAL,
                'type' => Coupon::TYPE_PERCENT,
                'amount' => 15,
                'starts_at' => now()->subDay(),
                'expires_at' => now()->addMonths(3),
                'max_uses' => 500,
                'max_uses_per_user' => 1,
                'is_active' => true,
                'auto_apply' => false,
            ],
        );

        Coupon::query()->updateOrCreate(
            ['code' => 'PHOTO10'],
            [
                'label' => 'Photographer service discount',
                'campaign' => Coupon::CAMPAIGN_SERVICE,
                'type' => Coupon::TYPE_PERCENT,
                'amount' => 10,
                'vendor_type_id' => $photographer?->id,
                'max_uses' => 200,
                'max_uses_per_user' => 1,
                'is_active' => true,
                'auto_apply' => false,
            ],
        );

        Coupon::query()->updateOrCreate(
            ['code' => 'FAHAD100'],
            [
                'label' => 'Fahad Studio Light offer',
                'campaign' => Coupon::CAMPAIGN_VENDOR,
                'type' => Coupon::TYPE_AMOUNT,
                'amount' => 100,
                'vendor_id' => $photoVendor->id,
                'max_uses' => 100,
                'max_uses_per_user' => 1,
                'is_active' => true,
                'auto_apply' => false,
            ],
        );

        Coupon::query()->updateOrCreate(
            ['code' => 'FIRSTSHOOT'],
            [
                'label' => 'First booking',
                'campaign' => Coupon::CAMPAIGN_FIRST_ORDER,
                'type' => Coupon::TYPE_AMOUNT,
                'amount' => 200,
                'max_uses' => 1000,
                'max_uses_per_user' => 1,
                'is_active' => true,
                'auto_apply' => true,
            ],
        );

        Coupon::query()->updateOrCreate(
            ['code' => 'LOYAL10'],
            [
                'label' => 'Loyal client 10%',
                'campaign' => Coupon::CAMPAIGN_LOYAL,
                'type' => Coupon::TYPE_PERCENT,
                'amount' => 10,
                'min_completed_bookings' => 2,
                'max_uses' => 1000,
                'max_uses_per_user' => 2,
                'is_active' => true,
                'auto_apply' => true,
            ],
        );
    }

    /**
     * Models filter catalog shown in admin Filter groups and the mobile Filter Models screen.
     */
    protected function seedModelMarketplaceFilters(): void
    {
        FilterGroup::query()->whereIn('slug', ['model_experience', 'model_sizes'])->update(['is_active' => false]);
        FilterGroup::query()->where('slug', 'budget')->update([
            'description' => 'Session slider from 500 EGP to 10,000+ EGP.',
        ]);

        $catalog = [
            'model_identity' => [
                ['Men', 'رجال', 'model-male', null, 0, null],
                ['Women', 'نساء', 'model-female', null, 0, null],
                ['Kids', 'أطفال', 'model-kids', null, 0, null],
            ],
            'model_category' => [
                ['Fashion', 'أزياء', 'fashion', null, 0, null],
                ['Commercial', 'تجاري', 'commercial', null, 0, null],
                ['Educational', 'تعليمي', 'model-educational', null, 0, null],
                ['Lifestyle', 'لايف ستايل', 'model-lifestyle', null, 0, null],
                ['Fitness', 'لياقة', 'model-fitness', null, 0, null],
                ['Promotional', 'ترويجي', 'model-promotional', null, 0, null],
                ['Podcast', 'بودكاست', 'model-podcast', null, 0, null],
                ['Talk', 'حوار', 'model-talk', null, 0, null],
                ['Acting', 'تمثيل', 'actor', null, 0, null],
                ['Corporate', 'شركات', 'model-corporate', null, 0, null],
                ['Social Media', 'سوشيال ميديا', 'model-social-media', null, 0, null],
                ['Other', 'أخرى', 'model-other', null, 0, null],
            ],
            'model_age' => [
                ['Under 18', 'أقل من 18', 'model-age-under-18', 'years', 0, 17],
                ['18 - 25', '18 - 25', 'model-age-18-25', 'years', 18, 25],
                ['26 - 35', '26 - 35', 'model-age-26-35', 'years', 26, 35],
                ['36 - 50', '36 - 50', 'model-age-36-50', 'years', 36, 50],
                ['50+', '50+', 'model-age-50-plus', 'years', 50, null],
            ],
            'model_height' => [
                ['Under 160', 'أقل من 160', 'model-height-under-160', 'cm', 0, 159],
                ['160 - 170', '160 - 170', 'model-height-160-170', 'cm', 160, 170],
                ['170 - 180', '170 - 180', 'model-height-170-180', 'cm', 170, 180],
                ['180 - 190', '180 - 190', 'model-height-180-190', 'cm', 180, 190],
                ['190 - 200', '190 - 200', 'model-height-190-200', 'cm', 190, 200],
                ['200+', '200+', 'model-height-200-plus', 'cm', 200, null],
            ],
            'model_tops' => [
                ['XS', 'XS', 'model-top-xs'],
                ['S', 'S', 'model-top-s'],
                ['M', 'M', 'model-top-m'],
                ['L', 'L', 'model-top-l'],
                ['XL', 'XL', 'model-top-xl'],
                ['XXL', 'XXL', 'model-top-xxl'],
            ],
            'model_pants' => [
                ['28', '28', 'model-waist-28'],
                ['30', '30', 'model-waist-30'],
                ['32', '32', 'model-waist-32'],
                ['34', '34', 'model-waist-34'],
                ['36', '36', 'model-waist-36'],
                ['38', '38', 'model-waist-38'],
            ],
            'model_shoes' => [
                ['39', '39', 'model-shoe-39', 'EU'],
                ['40', '40', 'model-shoe-40', 'EU'],
                ['41', '41', 'model-shoe-41', 'EU'],
                ['42', '42', 'model-shoe-42', 'EU'],
                ['43', '43', 'model-shoe-43', 'EU'],
                ['44', '44', 'model-shoe-44', 'EU'],
                ['45+', '45+', 'model-shoe-45-plus', 'EU', 45, null],
            ],
            'search_cities' => [
                ['Cairo', 'القاهرة', 'city-cairo'],
                ['Alexandria', 'الإسكندرية', 'city-alexandria'],
                ['Giza', 'الجيزة', 'city-giza'],
                ['Shubra El Kheima', 'شبرا الخيمة', 'city-shubra-el-kheima'],
                ['Port Said', 'بورسعيد', 'city-port-said'],
                ['Suez', 'السويس', 'city-suez'],
                ['Mansoura', 'المنصورة', 'city-mansoura'],
                ['El Mahalla El Kubra', 'المحلة الكبرى', 'city-el-mahalla-el-kubra'],
                ['Tanta', 'طنطا', 'city-tanta'],
                ['Assiut', 'أسيوط', 'city-assiut'],
                ['Fayoum', 'الفيوم', 'city-fayoum'],
                ['Zagazig', 'الزقازيق', 'city-zagazig'],
                ['Ismailia', 'الإسماعيلية', 'city-ismailia'],
                ['Aswan', 'أسوان', 'city-aswan'],
                ['Damanhur', 'دمنهور', 'city-damanhur'],
                ['Damietta', 'دمياط', 'city-damietta'],
                ['Minya', 'المنيا', 'city-minya'],
                ['Beni Suef', 'بني سويف', 'city-beni-suef'],
                ['Luxor', 'الأقصر', 'city-luxor'],
                ['Sohag', 'سوهاج', 'city-sohag'],
                ['Qena', 'قنا', 'city-qena'],
                ['Hurghada', 'الغردقة', 'city-hurghada'],
                ['Arish', 'العريش', 'city-arish'],
                ['Kafr El Sheikh', 'كفر الشيخ', 'city-kafr-el-sheikh'],
                ['Matrouh', 'مطروح', 'city-matrouh'],
                ['6th of October', 'السادس من أكتوبر', 'city-6th-of-october'],
                ['10th of Ramadan', 'العاشر من رمضان', 'city-10th-of-ramadan'],
            ],
        ];

        foreach ($catalog as $groupSlug => $options) {
            $group = FilterGroup::query()->where('slug', $groupSlug)->first();
            if (! $group) {
                continue;
            }

            foreach ($options as $i => $option) {
                [$nameEn, $nameAr, $slug] = $option;
                FilterTag::query()->updateOrCreate(['slug' => $slug], [
                    'filter_group_id' => $group->id,
                    'name_en' => $nameEn,
                    'name_ar' => $nameAr,
                    'helper_text' => null,
                    'group_key' => $groupSlug,
                    'vendor_type_slugs' => $group->vendor_type_slugs ?: [],
                    'unit' => $option[3] ?? null,
                    'min_value' => $option[4] ?? null,
                    'max_value' => $option[5] ?? null,
                    'is_active' => true,
                    'show_in_quick_filters' => $groupSlug === 'model_category',
                    'sort_order' => $i,
                ]);
            }
        }

        FilterTag::query()->whereIn('slug', [
            'editorial',
            'ecommerce-model',
            'model-height',
            'model-clothing',
            'model-shoes',
            'model-hair-eye',
        ])->update(['is_active' => false]);
    }

    /**
     * Studios filter catalog shown in admin Filter groups and the mobile Filter Studios screen.
     */
    protected function seedStudioMarketplaceFilters(): void
    {
        $catalog = [
            'studio_type' => [
                ['All Types', 'كل الأنواع', 'studio-type-all'],
                ['Photo Studio', 'استوديو تصوير', 'studio-type-photo'],
                ['Video Studio', 'استوديو فيديو', 'studio-type-video'],
                ['Podcast Studio', 'استوديو بودكاست', 'studio-type-podcast'],
                ['Event Space', 'قاعة فعاليات', 'studio-type-event'],
                ['Cyclorama', 'سيكلوراما', 'studio-type-cyclorama'],
                ['Green Screen', 'شاشة خضراء', 'studio-type-green-screen'],
                ['Other', 'أخرى', 'studio-type-other'],
            ],
            'studio_areas' => [
                ['Cairo', 'القاهرة', 'studio-area-cairo'],
                ['New Cairo', 'القاهرة الجديدة', 'studio-area-new-cairo'],
                ['Zamalek', 'الزمالك', 'studio-area-zamalek'],
                ['Maadi', 'المعادي', 'studio-area-maadi'],
                ['6th of October', 'السادس من أكتوبر', 'studio-area-6th-of-october'],
                ['Sheikh Zayed', 'الشيخ زايد', 'studio-area-sheikh-zayed'],
                ['Nasr City', 'مدينة نصر', 'studio-area-nasr-city'],
                ['Heliopolis', 'مصر الجديدة', 'studio-area-heliopolis'],
                ['Other', 'أخرى', 'studio-area-other'],
            ],
            'studio_hourly' => [
                ['Under 100', 'أقل من 100', 'studio-hourly-under-100', 'EGP/hour', 0, 99],
                ['100 - 250', '100 - 250', 'studio-hourly-100-250', 'EGP/hour', 100, 250],
                ['250 - 500', '250 - 500', 'studio-hourly-250-500', 'EGP/hour', 250, 500],
                ['500 - 1,000', '500 - 1,000', 'studio-hourly-500-1000', 'EGP/hour', 500, 1000],
                ['1,000 - 2,000', '1,000 - 2,000', 'studio-hourly-1000-2000', 'EGP/hour', 1000, 2000],
                ['2,000+', '2,000+', 'studio-hourly-2000-plus', 'EGP/hour', 2000, null],
            ],
            'studio_size' => [
                ['Under 50', 'أقل من 50', 'studio-size-under-50', 'm²', 0, 49],
                ['50 - 100', '50 - 100', 'studio-size-50-100', 'm²', 50, 100],
                ['100 - 200', '100 - 200', 'studio-size-100-200', 'm²', 100, 200],
                ['200 - 500', '200 - 500', 'studio-size-200-500', 'm²', 200, 500],
                ['500 - 1,000', '500 - 1,000', 'studio-size-500-1000', 'm²', 500, 1000],
                ['1,000+', '1,000+', 'studio-size-1000-plus', 'm²', 1000, null],
            ],
            'studio_features' => [
                ['All Equipment', 'كل التجهيزات', 'studio-equipment-all'],
                ['Lighting', 'إضاءة', 'studio-lights'],
                ['Green Screen', 'شاشة خضراء', 'green-screen'],
                ['Cyclorama', 'سيكلوراما', 'cyclorama'],
                ['Sound Equipment', 'معدات صوت', 'studio-sound'],
                ['Podcast Setup', 'إعداد بودكاست', 'podcast-room'],
                ['Backdrops', 'خلفيات', 'studio-backdrops'],
                ['Props', 'إكسسوارات', 'props'],
                ['Makeup Room', 'غرفة مكياج', 'makeup-room'],
                ['Changing Room', 'غرفة تغيير', 'studio-changing'],
                ['Parking', 'موقف سيارات', 'parking'],
                ['Wi-Fi', 'واي فاي', 'studio-wifi'],
                ['Air Conditioning', 'تكييف', 'ac'],
                ['Kitchen', 'مطبخ', 'kitchen-set'],
                ['Natural Light', 'إضاءة طبيعية', 'studio-natural-light'],
                ['Other', 'أخرى', 'studio-feature-other'],
            ],
            'rating' => [
                ['Any Rating', 'أي تقييم', 'rating-any-rating'],
                ['3.5+', '3.5+', 'rating-3-5'],
            ],
        ];

        foreach ($catalog as $groupSlug => $options) {
            $group = FilterGroup::query()->where('slug', $groupSlug)->first();
            if (! $group) {
                continue;
            }

            foreach ($options as $i => $option) {
                [$nameEn, $nameAr, $slug] = $option;
                $existing = FilterTag::query()->where('slug', $slug)->first();
                FilterTag::query()->updateOrCreate(['slug' => $slug], [
                    'filter_group_id' => $groupSlug === 'rating' ? ($existing?->filter_group_id ?? $group->id) : $group->id,
                    'name_en' => $nameEn,
                    'name_ar' => $nameAr,
                    'helper_text' => $existing?->helper_text,
                    'group_key' => $groupSlug === 'rating' ? 'rating' : $groupSlug,
                    'vendor_type_slugs' => $groupSlug === 'rating' ? [] : ($group->vendor_type_slugs ?: ['studio']),
                    'unit' => $option[3] ?? $existing?->unit,
                    'min_value' => $option[4] ?? $existing?->min_value,
                    'max_value' => $option[5] ?? $existing?->max_value,
                    'is_active' => true,
                    'show_in_quick_filters' => in_array($groupSlug, ['studio_type', 'studio_features'], true),
                    'sort_order' => $groupSlug === 'rating' ? ($existing?->sort_order ?? (80 + $i)) : $i,
                ]);
            }
        }
    }

    /**
     * UGC filter catalog shown in admin Filter groups and the mobile Filter UGC Creators screen.
     */
    protected function seedUgcMarketplaceFilters(): void
    {
        FilterTag::query()->updateOrCreate(['slug' => 'availability-any-time'], [
            'filter_group_id' => FilterGroup::query()->where('slug', 'availability')->value('id'),
            'name_en' => 'Any Time',
            'name_ar' => 'أي وقت',
            'group_key' => 'availability',
            'vendor_type_slugs' => [],
            'synonyms' => ['any time'],
            'is_active' => true,
            'sort_order' => 90,
        ]);

        $catalog = [
            'ugc_niche' => [
                ['All Categories', 'كل التصنيفات', 'ugc-niche-all'],
                ['Beauty', 'جمال', 'ugc-niche-beauty'],
                ['Fashion', 'أزياء', 'ugc-niche-fashion'],
                ['Lifestyle', 'لايف ستايل', 'ugc-niche-lifestyle'],
                ['Food & Beverages', 'طعام ومشروبات', 'ugc-niche-food'],
                ['Travel', 'سفر', 'ugc-niche-travel'],
                ['Fitness', 'لياقة', 'ugc-niche-fitness'],
                ['Home & Living', 'منزل ومعيشة', 'ugc-niche-home'],
                ['Parenting', 'أسرة', 'ugc-niche-parenting'],
                ['Gaming', 'ألعاب', 'ugc-niche-gaming'],
                ['Electronics', 'إلكترونيات', 'ugc-niche-electronics'],
                ['Other', 'أخرى', 'ugc-niche-other'],
            ],
            'ugc_cities' => [
                ['All Cities', 'كل المدن', 'ugc-city-all'],
                ['Cairo', 'القاهرة', 'ugc-city-cairo'],
                ['Alexandria', 'الإسكندرية', 'ugc-city-alexandria'],
                ['Giza', 'الجيزة', 'ugc-city-giza'],
                ['New Cairo', 'القاهرة الجديدة', 'ugc-city-new-cairo'],
                ['6th of October', 'السادس من أكتوبر', 'ugc-city-6th-of-october'],
                ['Sharm El Sheikh', 'شرم الشيخ', 'ugc-city-sharm'],
                ['Hurghada', 'الغردقة', 'ugc-city-hurghada'],
                ['Port Said', 'بورسعيد', 'ugc-city-port-said'],
                ['Suez', 'السويس', 'ugc-city-suez'],
                ['Mansoura', 'المنصورة', 'ugc-city-mansoura'],
                ['Tanta', 'طنطا', 'ugc-city-tanta'],
                ['Assiut', 'أسيوط', 'ugc-city-assiut'],
                ['Fayoum', 'الفيوم', 'ugc-city-fayoum'],
                ['Zagazig', 'الزقازيق', 'ugc-city-zagazig'],
                ['Ismailia', 'الإسماعيلية', 'ugc-city-ismailia'],
                ['Aswan', 'أسوان', 'ugc-city-aswan'],
                ['Damanhur', 'دمنهور', 'ugc-city-damanhur'],
                ['Damietta', 'دمياط', 'ugc-city-damietta'],
                ['Minya', 'المنيا', 'ugc-city-minya'],
                ['Beni Suef', 'بني سويف', 'ugc-city-beni-suef'],
                ['Luxor', 'الأقصر', 'ugc-city-luxor'],
                ['Sohag', 'سوهاج', 'ugc-city-sohag'],
                ['Qena', 'قنا', 'ugc-city-qena'],
                ['Matrouh', 'مطروح', 'ugc-city-matrouh'],
                ['Kafr El Sheikh', 'كفر الشيخ', 'ugc-city-kafr-el-sheikh'],
                ['Arish', 'العريش', 'ugc-city-arish'],
                ['Other', 'أخرى', 'ugc-city-other'],
            ],
            'ugc_accent' => [
                ['All Accents', 'كل اللهجات', 'ugc-accent-all'],
                ['Egyptian', 'مصرية', 'ugc-accent-egyptian'],
                ['Saudi', 'سعودية', 'ugc-accent-saudi'],
                ['UAE', 'إماراتية', 'ugc-accent-uae'],
                ['Kuwaiti', 'كويتية', 'ugc-accent-kuwaiti'],
                ['Shami (Levantine)', 'شامية', 'ugc-accent-shami'],
                ['Other', 'أخرى', 'ugc-accent-other'],
            ],
            'ugc_followers' => [
                ['Under 1K', 'أقل من 1K', 'ugc-followers-under-1k', 'followers', 0, 999],
                ['1K - 10K', '1K - 10K', 'ugc-followers-1k-10k', 'followers', 1000, 10000],
                ['10K - 50K', '10K - 50K', 'ugc-followers-10k-50k', 'followers', 10000, 50000],
                ['50K - 100K', '50K - 100K', 'ugc-followers-50k-100k', 'followers', 50000, 100000],
                ['100K - 500K', '100K - 500K', 'ugc-followers-100k-500k', 'followers', 100000, 500000],
                ['500K - 1M+', '500K - 1M+', 'ugc-followers-500k-1m', 'followers', 500000, null],
            ],
            'ugc_price' => [
                ['Under 500', 'أقل من 500', 'ugc-price-under-500', 'EGP/video', 0, 499],
                ['500 - 1,000', '500 - 1,000', 'ugc-price-500-1000', 'EGP/video', 500, 1000],
                ['1,000 - 2,500', '1,000 - 2,500', 'ugc-price-1000-2500', 'EGP/video', 1000, 2500],
                ['2,500 - 5,000', '2,500 - 5,000', 'ugc-price-2500-5000', 'EGP/video', 2500, 5000],
                ['5,000 - 10,000', '5,000 - 10,000', 'ugc-price-5000-10000', 'EGP/video', 5000, 10000],
                ['10,000+', '10,000+', 'ugc-price-10000-plus', 'EGP/video', 10000, null],
            ],
            'ugc_content' => [
                ['All Types', 'كل الأنواع', 'ugc-content-all'],
                ['Video', 'فيديو', 'ugc-content-video'],
                ['Unboxing', 'فتح علب', 'unboxing'],
                ['Product Demo', 'عرض منتج', 'ugc-content-demo'],
                ['Testimonial', 'تجربة عميل', 'ugc-content-testimonial'],
                ['Tutorial', 'شرح', 'ugc-content-tutorial'],
                ['Lifestyle', 'لايف ستايل', 'ugc-content-lifestyle'],
                ['Trend / Challenge', 'ترند / تحدي', 'ugc-content-trend'],
                ['UGC Ads', 'إعلانات UGC', 'ugc-content-ads'],
                ['Voiceover', 'تعليق صوتي', 'voiceover'],
                ['Other', 'أخرى', 'ugc-content-other'],
            ],
            'ugc_gender' => [
                ['All', 'الكل', 'ugc-gender-all'],
                ['Men', 'رجال', 'ugc-gender-men'],
                ['Women', 'نساء', 'ugc-gender-women'],
                ['Non-binary', 'غير ثنائي', 'ugc-gender-nonbinary'],
            ],
            'ugc_age' => [
                ['All Ages', 'كل الأعمار', 'ugc-age-all'],
                ['18 - 24', '18 - 24', 'ugc-age-18-24', 'years', 18, 24],
                ['25 - 34', '25 - 34', 'ugc-age-25-34', 'years', 25, 34],
                ['35 - 44', '35 - 44', 'ugc-age-35-44', 'years', 35, 44],
                ['45+', '45+', 'ugc-age-45-plus', 'years', 45, null],
            ],
            'ugc_language' => [
                ['All Languages', 'كل اللغات', 'ugc-language-all'],
                ['Arabic', 'العربية', 'ugc-language-arabic'],
                ['English', 'الإنجليزية', 'ugc-language-english'],
                ['Bilingual', 'ثنائي اللغة', 'ugc-language-bilingual'],
                ['Other', 'أخرى', 'ugc-language-other'],
            ],
        ];

        foreach ($catalog as $groupSlug => $options) {
            $group = FilterGroup::query()->where('slug', $groupSlug)->first();
            if (! $group) {
                continue;
            }

            foreach ($options as $i => $option) {
                [$nameEn, $nameAr, $slug] = $option;
                FilterTag::query()->updateOrCreate(['slug' => $slug], [
                    'filter_group_id' => $group->id,
                    'name_en' => $nameEn,
                    'name_ar' => $nameAr,
                    'group_key' => $groupSlug,
                    'vendor_type_slugs' => $group->vendor_type_slugs ?: ['ugc'],
                    'unit' => $option[3] ?? null,
                    'min_value' => $option[4] ?? null,
                    'max_value' => $option[5] ?? null,
                    'is_active' => true,
                    'show_in_quick_filters' => in_array($groupSlug, ['ugc_niche', 'ugc_content'], true),
                    'sort_order' => $i,
                ]);
            }
        }
    }

    /**
     * Food stylist filter catalog shown in admin and Filter Food Stylists.
     */
    protected function seedFoodStylistMarketplaceFilters(): void
    {
        $catalog = [
            'food_expertise' => [
                ['All Expertise', 'كل الخبرات', 'food-expertise-all'],
                ['Recipe Development', 'تطوير وصفات', 'recipe-dev'],
                ['Food Styling', 'تنسيق طعام', 'food-staging'],
                ['Food Photography', 'تصوير طعام', 'food-expertise-photo'],
                ['Food Videography', 'فيديو طعام', 'food-expertise-video'],
                ['Props Styling', 'تنسيق إكسسوار', 'prop-sourcing'],
            ],
            'food_cuisine' => [
                ['All Cuisines', 'كل المطابخ', 'food-cuisine-all'],
                ['Egyptian', 'مصري', 'food-cuisine-egyptian'],
                ['Italian', 'إيطالي', 'food-cuisine-italian'],
                ['Asian', 'آسيوي', 'food-cuisine-asian'],
                ['Middle Eastern', 'شرق أوسطي', 'food-cuisine-middle-eastern'],
                ['Healthy', 'صحي', 'food-cuisine-healthy'],
                ['Desserts & Bakery', 'حلويات ومخبوزات', 'food-cuisine-desserts'],
                ['Beverages', 'مشروبات', 'food-cuisine-beverages'],
                ['Home Cooking', 'طبخ منزلي', 'food-cuisine-home'],
                ['Fine Dining', 'فاين دايننج', 'food-cuisine-fine'],
                ['Other', 'أخرى', 'food-cuisine-other'],
            ],
            'food_cities' => [
                ['All Cities', 'كل المدن', 'food-city-all'],
                ['Cairo', 'القاهرة', 'food-city-cairo'],
                ['Alexandria', 'الإسكندرية', 'food-city-alexandria'],
                ['Giza', 'الجيزة', 'food-city-giza'],
                ['Sharm El Sheikh', 'شرم الشيخ', 'food-city-sharm'],
                ['Hurghada', 'الغردقة', 'food-city-hurghada'],
                ['Mansoura', 'المنصورة', 'food-city-mansoura'],
                ['Tanta', 'طنطا', 'food-city-tanta'],
                ['Ismailia', 'الإسماعيلية', 'food-city-ismailia'],
                ['Suez', 'السويس', 'food-city-suez'],
                ['Luxor', 'الأقصر', 'food-city-luxor'],
                ['Aswan', 'أسوان', 'food-city-aswan'],
                ['Port Said', 'بورسعيد', 'food-city-port-said'],
                ['Damietta', 'دمياط', 'food-city-damietta'],
                ['Zagazig', 'الزقازيق', 'food-city-zagazig'],
                ['Minya', 'المنيا', 'food-city-minya'],
                ['Sohag', 'سوهاج', 'food-city-sohag'],
                ['Qena', 'قنا', 'food-city-qena'],
                ['Beni Suef', 'بني سويف', 'food-city-beni-suef'],
                ['Other', 'أخرى', 'food-city-other'],
            ],
            'food_price' => [
                ['Under 500', 'أقل من 500', 'food-price-under-500', 'EGP/video', 0, 499],
                ['500 - 1,000', '500 - 1,000', 'food-price-500-1000', 'EGP/video', 500, 1000],
                ['1,000 - 2,500', '1,000 - 2,500', 'food-price-1000-2500', 'EGP/video', 1000, 2500],
                ['2,500 - 5,000', '2,500 - 5,000', 'food-price-2500-5000', 'EGP/video', 2500, 5000],
                ['5,000 - 10,000', '5,000 - 10,000', 'food-price-5000-10000', 'EGP/video', 5000, 10000],
                ['10,000+', '10,000+', 'food-price-10000-plus', 'EGP/video', 10000, null],
            ],
            'food_content' => [
                ['All Types', 'كل الأنواع', 'food-content-all'],
                ['Photo', 'صورة', 'food-content-photo'],
                ['Video', 'فيديو', 'food-content-video'],
                ['Recipe Video', 'فيديو وصفة', 'food-content-recipe-video'],
                ['Tutorial', 'شرح', 'food-content-tutorial'],
                ['Product Shoot', 'تصوير منتج', 'food-content-product'],
                ['Lifestyle', 'لايف ستايل', 'food-content-lifestyle'],
                ['Social Media Content', 'محتوى سوشيال', 'food-content-social'],
            ],
        ];

        foreach ($catalog as $groupSlug => $options) {
            $group = FilterGroup::query()->where('slug', $groupSlug)->first();
            if (! $group) {
                continue;
            }

            foreach ($options as $i => $option) {
                [$nameEn, $nameAr, $slug] = $option;
                FilterTag::query()->updateOrCreate(['slug' => $slug], [
                    'filter_group_id' => $group->id,
                    'name_en' => $nameEn,
                    'name_ar' => $nameAr,
                    'group_key' => $groupSlug,
                    'vendor_type_slugs' => ['food_stylist'],
                    'unit' => $option[3] ?? null,
                    'min_value' => $option[4] ?? null,
                    'max_value' => $option[5] ?? null,
                    'is_active' => true,
                    'show_in_quick_filters' => $groupSlug === 'food_expertise',
                    'sort_order' => $i,
                ]);
            }
        }
    }

    /**
     * Extra marketplace vendors so every Home section has people with photos.
     */
    protected function seedHomeShowcaseVendors(?int $cairoId, ?int $alexandriaId): void
    {
        $rows = [
            ['email' => 'amira.photo@lens.app', 'phone' => '01055550011', 'name' => 'Amira Hassan', 'slug' => 'photographer', 'display' => 'Amira Frames', 'city' => $cairoId, 'rating' => 4.8, 'count' => 22, 'half' => 1600, 'full' => 2800],
            ['email' => 'hossam.photo@lens.app', 'phone' => '01055550022', 'name' => 'Hossam Light', 'slug' => 'photographer', 'display' => 'Hossam Light', 'city' => $cairoId, 'rating' => 4.7, 'count' => 18, 'half' => 1500, 'full' => 2700],
            ['email' => 'nour.photo@lens.app', 'phone' => '01055550023', 'name' => 'Nour Portraits', 'slug' => 'photographer', 'display' => 'Nour Portraits', 'city' => $alexandriaId, 'rating' => 4.9, 'count' => 31, 'half' => 1700, 'full' => 3000],
            ['email' => 'youssef.photo@lens.app', 'phone' => '01055550024', 'name' => 'Youssef Streets', 'slug' => 'photographer', 'display' => 'Youssef Streets', 'city' => $cairoId, 'rating' => 4.6, 'count' => 14, 'half' => 1400, 'full' => 2500],
            ['email' => 'karim.video@lens.app', 'phone' => '01055550012', 'name' => 'Karim Motion', 'slug' => 'videographer', 'display' => 'Karim Motion', 'city' => $cairoId, 'rating' => 4.8, 'count' => 96, 'half' => 2200, 'full' => 3900],
            ['email' => 'layla.video@lens.app', 'phone' => '01055550013', 'name' => 'Layla Films', 'slug' => 'videographer', 'display' => 'Layla Films', 'city' => $alexandriaId, 'rating' => 4.7, 'count' => 41, 'half' => 1900, 'full' => 3400],
            ['email' => 'tarek.video@lens.app', 'phone' => '01055550025', 'name' => 'Tarek Cinema', 'slug' => 'videographer', 'display' => 'Tarek Cinema', 'city' => $cairoId, 'rating' => 4.9, 'count' => 52, 'half' => 2400, 'full' => 4100],
            ['email' => 'salma.video@lens.app', 'phone' => '01055550026', 'name' => 'Salma Docs', 'slug' => 'videographer', 'display' => 'Salma Docs', 'city' => $cairoId, 'rating' => 4.6, 'count' => 19, 'half' => 1800, 'full' => 3200],
            ['email' => 'reels.mona@lens.app', 'phone' => '01055550016', 'name' => 'Mona Reels', 'slug' => 'reels', 'display' => 'Mona Reels', 'city' => $cairoId, 'rating' => 4.9, 'count' => 54, 'half' => 1200, 'full' => 2100],
            ['email' => 'reels.habiba@lens.app', 'phone' => '01055550027', 'name' => 'Habiba Clips', 'slug' => 'reels', 'display' => 'Habiba Clips', 'city' => $cairoId, 'rating' => 4.8, 'count' => 27, 'half' => 1100, 'full' => 1900],
            ['email' => 'reels.ziad@lens.app', 'phone' => '01055550028', 'name' => 'Ziad Reels', 'slug' => 'reels', 'display' => 'Ziad Reels', 'city' => $alexandriaId, 'rating' => 4.7, 'count' => 21, 'half' => 1000, 'full' => 1800],
            ['email' => 'reels.farah@lens.app', 'phone' => '01055550029', 'name' => 'Farah Mobile', 'slug' => 'reels', 'display' => 'Farah Mobile', 'city' => $cairoId, 'rating' => 4.6, 'count' => 16, 'half' => 950, 'full' => 1700],
            ['email' => 'model.yasmin@lens.app', 'phone' => '01055550017', 'name' => 'Yasmin Atelier', 'slug' => 'model', 'display' => 'Yasmin Atelier', 'city' => $cairoId, 'rating' => 4.8, 'count' => 33, 'half' => 1400, 'full' => 2500],
            ['email' => 'model.laila@lens.app', 'phone' => '01055550030', 'name' => 'Laila Cast', 'slug' => 'model', 'display' => 'Laila Cast', 'city' => $cairoId, 'rating' => 4.9, 'count' => 28, 'half' => 1500, 'full' => 2600],
            ['email' => 'model.adam@lens.app', 'phone' => '01055550031', 'name' => 'Adam Runway', 'slug' => 'model', 'display' => 'Adam Runway', 'city' => $alexandriaId, 'rating' => 4.7, 'count' => 20, 'half' => 1300, 'full' => 2300],
            ['email' => 'model.mariam@lens.app', 'phone' => '01055550032', 'name' => 'Mariam Studio', 'slug' => 'model', 'display' => 'Mariam Studio', 'city' => $cairoId, 'rating' => 4.6, 'count' => 15, 'half' => 1200, 'full' => 2200],
            ['email' => 'nile.studio@lens.app', 'phone' => '01055550014', 'name' => 'Rania Loft', 'slug' => 'studio', 'display' => 'Nile Loft Studio', 'city' => $cairoId, 'rating' => 4.7, 'count' => 28, 'hourly' => 280],
            ['email' => 'giza.studio@lens.app', 'phone' => '01055550015', 'name' => 'Omar Daylight', 'slug' => 'studio', 'display' => 'Giza Daylight', 'city' => $cairoId, 'rating' => 4.5, 'count' => 15, 'hourly' => 220],
            ['email' => 'blackbox.studio@lens.app', 'phone' => '01055550020', 'name' => 'Maya Blackbox', 'slug' => 'studio', 'display' => 'Cairo Blackbox', 'city' => $cairoId, 'rating' => 4.6, 'count' => 21, 'hourly' => 300],
            ['email' => 'zamalek.studio@lens.app', 'phone' => '01055550021', 'name' => 'Hana Cyc', 'slug' => 'studio', 'display' => 'Zamalek Cyc', 'city' => $cairoId, 'rating' => 4.8, 'count' => 12, 'hourly' => 260],
            ['email' => 'ugc.omar@lens.app', 'phone' => '01055550018', 'name' => 'Omar UGC', 'slug' => 'ugc', 'display' => 'Omar UGC', 'city' => $cairoId, 'rating' => 4.6, 'count' => 19, 'per_video' => 850],
            ['email' => 'ugc.nada@lens.app', 'phone' => '01055550033', 'name' => 'Nada Social', 'slug' => 'ugc', 'display' => 'Nada Social', 'city' => $cairoId, 'rating' => 4.8, 'count' => 24, 'per_video' => 900],
            ['email' => 'ugc.kareem@lens.app', 'phone' => '01055550034', 'name' => 'Kareem Content', 'slug' => 'ugc', 'display' => 'Kareem Content', 'city' => $alexandriaId, 'rating' => 4.7, 'count' => 17, 'per_video' => 800],
            ['email' => 'ugc.hana@lens.app', 'phone' => '01055550035', 'name' => 'Hana Creator', 'slug' => 'ugc', 'display' => 'Hana Creator', 'city' => $cairoId, 'rating' => 4.9, 'count' => 29, 'per_video' => 950],
            ['email' => 'food.dina@lens.app', 'phone' => '01055550019', 'name' => 'Dina Plates', 'slug' => 'food_stylist', 'display' => 'Dina Plates', 'city' => $cairoId, 'rating' => 4.8, 'count' => 17, 'half' => 1500, 'full' => 2600],
            ['email' => 'food.mariam@lens.app', 'phone' => '01055550036', 'name' => 'Chef Mariam', 'slug' => 'food_stylist', 'display' => 'Chef Mariam', 'city' => $cairoId, 'rating' => 4.9, 'count' => 23, 'half' => 1600, 'full' => 2800],
            ['email' => 'food.bassem@lens.app', 'phone' => '01055550037', 'name' => 'Bassem Table', 'slug' => 'food_stylist', 'display' => 'Bassem Table', 'city' => $alexandriaId, 'rating' => 4.6, 'count' => 11, 'half' => 1400, 'full' => 2400],
            ['email' => 'food.sara@lens.app', 'phone' => '01055550038', 'name' => 'Sara Styling', 'slug' => 'food_stylist', 'display' => 'Sara Styling', 'city' => $cairoId, 'rating' => 4.7, 'count' => 15, 'half' => 1450, 'full' => 2500],
        ];

        foreach ($rows as $index => $row) {
            $type = VendorType::query()->where('slug', $row['slug'])->first();
            if (! $type) {
                continue;
            }

            $photoSeed = $index + 2;

            $user = User::query()->updateOrCreate(
                ['email' => $row['email']],
                [
                    'name' => $row['name'],
                    'phone' => $row['phone'],
                    'password' => Hash::make('password'),
                    'role' => 'vendor',
                    'city_id' => $row['city'],
                    'is_active' => true,
                    'locale' => 'en',
                ],
            );

            $vendor = Vendor::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'vendor_type_id' => $type->id,
                    'city_id' => $row['city'],
                    'display_name' => $row['display'],
                    'bio' => $row['display'].' on Lens.',
                    'verification_status' => 'verified',
                    'is_active' => true,
                    'is_featured' => ($row['rating'] ?? 0) >= 4.8,
                    'profile_photo' => VendorPhotos::portrait($row['slug'], $photoSeed),
                    'cover_image' => VendorPhotos::cover($row['slug'], $index),
                    'half_day_price' => $row['half'] ?? null,
                    'full_day_price' => $row['full'] ?? null,
                    'hourly_price' => $row['hourly'] ?? null,
                    'per_video_price' => $row['per_video'] ?? null,
                    'completed_sessions' => 12 + $index,
                    'booked_sessions' => 16 + $index,
                    'accepted_sessions' => 14 + $index,
                    'rejected_sessions' => 1,
                    'failed_sessions' => 0,
                    'rating_avg' => $row['rating'],
                    'rating_count' => $row['count'],
                    'response_minutes' => 20,
                ],
            );

            $badgeSlugs = ['verified'];
            if (($row['rating'] ?? 0) >= 4.8) {
                $badgeSlugs[] = 'top-rated';
            }

            $vendor->badges()->sync(Badge::query()->whereIn('slug', $badgeSlugs)->pluck('id'));

            $tagSets = [
                'photographer' => [['fnb', 'events', 'product'], ['fnb', 'photosession'], ['wedding', 'events'], ['product', 'corporate']],
                'videographer' => [['events', 'wedding', 'corporate'], ['wedding', 'photosession'], ['events', 'corporate'], ['fnb', 'events']],
                'reels' => [['fnb', 'product'], ['events', 'photosession'], ['product', 'corporate'], ['fnb', 'events']],
                'model' => [['wedding', 'photosession'], ['product', 'corporate'], ['events', 'wedding'], ['photosession', 'product']],
                'studio' => [['product', 'fnb'], ['wedding', 'events'], ['corporate', 'photosession'], ['fnb', 'product']],
                'ugc' => [['fnb', 'product'], ['events', 'corporate'], ['product', 'photosession'], ['fnb', 'events']],
                'food_stylist' => [['fnb', 'product'], ['fnb', 'events'], ['fnb', 'corporate'], ['fnb', 'photosession']],
            ];
            $sets = $tagSets[$row['slug']] ?? [['events']];
            $vendor->categories()->sync(
                Category::query()->whereIn('slug', $sets[$index % count($sets)])->pluck('id')
            );

            $modelTags = [
                'Yasmin Atelier' => ['model-female', 'fashion', 'commercial', 'model-age-18-25', 'model-height-170-180', 'model-top-m', 'model-waist-32', 'model-shoe-41', 'city-cairo'],
                'Laila Cast' => ['model-female', 'fashion', 'model-lifestyle', 'model-age-18-25', 'model-height-160-170', 'model-top-s', 'model-waist-30', 'model-shoe-40', 'city-cairo'],
                'Adam Runway' => ['model-male', 'fashion', 'commercial', 'model-age-26-35', 'model-height-180-190', 'model-top-l', 'model-waist-32', 'model-shoe-43', 'city-alexandria'],
                'Mariam Studio' => ['model-female', 'actor', 'model-corporate', 'model-age-26-35', 'model-height-160-170', 'model-top-m', 'model-waist-32', 'model-shoe-41', 'city-cairo'],
            ];
            if ($row['slug'] === 'model' && isset($modelTags[$row['display']])) {
                $vendor->filterTags()->sync(
                    FilterTag::query()->whereIn('slug', $modelTags[$row['display']])->pluck('id')
                );
            }

            $studioTags = [
                'Nile Loft Studio' => ['studio-type-photo', 'studio-area-maadi', 'studio-lights', 'studio-natural-light', 'ac'],
                'Giza Daylight' => ['studio-type-photo', 'studio-area-6th-of-october', 'studio-natural-light', 'studio-lights', 'parking'],
                'Cairo Blackbox' => ['studio-type-video', 'studio-area-cairo', 'green-screen', 'studio-sound', 'studio-lights'],
                'Zamalek Cyc' => ['studio-type-cyclorama', 'studio-area-zamalek', 'cyclorama', 'studio-lights', 'makeup-room'],
            ];
            if ($row['slug'] === 'studio' && isset($studioTags[$row['display']])) {
                $vendor->filterTags()->sync(
                    FilterTag::query()->whereIn('slug', $studioTags[$row['display']])->pluck('id')
                );
            }

            $ugcTags = [
                'Omar UGC' => ['ugc-niche-lifestyle', 'ugc-city-cairo', 'ugc-accent-egyptian', 'unboxing', 'ugc-gender-men', 'ugc-language-arabic'],
                'Nada Social' => ['ugc-niche-beauty', 'ugc-city-cairo', 'ugc-accent-egyptian', 'ugc-content-video', 'ugc-gender-women', 'ugc-language-arabic'],
                'Kareem Content' => ['ugc-niche-gaming', 'ugc-city-alexandria', 'ugc-accent-egyptian', 'ugc-content-tutorial', 'ugc-gender-men', 'ugc-language-english'],
                'Hana Creator' => ['ugc-niche-fashion', 'ugc-city-cairo', 'ugc-accent-egyptian', 'ugc-content-ads', 'ugc-gender-women', 'ugc-language-bilingual'],
            ];
            if ($row['slug'] === 'ugc' && isset($ugcTags[$row['display']])) {
                $vendor->filterTags()->sync(
                    FilterTag::query()->whereIn('slug', $ugcTags[$row['display']])->pluck('id')
                );
            }

            $foodTags = [
                'Dina Plates' => ['recipe-dev', 'food-cuisine-egyptian', 'food-city-cairo', 'food-content-photo'],
                'Chef Mariam' => ['food-staging', 'food-cuisine-middle-eastern', 'food-city-cairo', 'food-content-recipe-video'],
                'Bassem Table' => ['food-expertise-photo', 'food-cuisine-fine', 'food-city-alexandria', 'food-content-product'],
                'Sara Styling' => ['prop-sourcing', 'food-cuisine-desserts', 'food-city-cairo', 'food-content-social'],
            ];
            if ($row['slug'] === 'food_stylist' && isset($foodTags[$row['display']])) {
                $vendor->filterTags()->sync(
                    FilterTag::query()->whereIn('slug', $foodTags[$row['display']])->pluck('id')
                );
            }
        }
    }

    protected function seedClientInbox(): void
    {
        $client = User::query()->where('email', 'client@lens.app')->first();
        if (! $client) {
            return;
        }

        $vendors = Vendor::query()->where('is_active', true)->orderByDesc('rating_avg')->limit(3)->get();
        foreach ($vendors as $vendor) {
            Favorite::query()->updateOrCreate([
                'user_id' => $client->id,
                'vendor_id' => $vendor->id,
            ]);
        }

        if ($client->notifications()->count() > 0) {
            return;
        }

        LensNotifier::toUser($client, LensNotifier::BOOKING_ACCEPTED, 'Request accepted', 'Fahad Studio Light accepted your booking LN-1001.');
        LensNotifier::toUser($client, LensNotifier::PAYMENT, 'Payment received', 'EGP 1,800.00 is held in escrow for LN-1001.');
        LensNotifier::toUser($client, LensNotifier::OFFER, 'New offer', 'PING50 is now available on Lens.');
        LensNotifier::toUser($client, LensNotifier::APP_NOTICE, 'Welcome to Lens', 'Tap the heart on a creator to save them in Favorites.');
    }
}
