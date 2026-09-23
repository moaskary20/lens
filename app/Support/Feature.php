<?php

namespace App\Support;

use App\Models\Setting;

class Feature
{
    /**
     * @var array<string, array{label: string, description: string, default: bool}>
     */
    public const CATALOG = [
        'clients' => ['label' => 'Clients', 'description' => 'Manage client accounts', 'default' => true],
        'vendors' => ['label' => 'Vendors', 'description' => 'Manage photographers, studios, and creators', 'default' => true],
        'vendor_photographers' => ['label' => 'Type: Photographers', 'description' => 'Enable photographer vendors', 'default' => true],
        'vendor_videographers' => ['label' => 'Type: Videographers', 'description' => 'Enable videographer vendors', 'default' => true],
        'vendor_reels' => ['label' => 'Type: Reels creators', 'description' => 'Enable mobile reels creators', 'default' => true],
        'vendor_studios' => ['label' => 'Type: Studios', 'description' => 'Enable studio vendors', 'default' => true],
        'vendor_models' => ['label' => 'Type: Models', 'description' => 'Enable model vendors', 'default' => true],
        'vendor_ugc' => ['label' => 'Type: UGC creators', 'description' => 'Enable UGC creators', 'default' => true],
        'vendor_food_stylists' => ['label' => 'Type: Food stylists', 'description' => 'Enable food stylist vendors', 'default' => true],
        'verification' => ['label' => 'Vendor verification', 'description' => 'Review and approve vendor accounts', 'default' => true],
        'categories' => ['label' => 'Categories', 'description' => 'Project categories (weddings, products, etc.)', 'default' => true],
        'filters' => ['label' => 'Search filters', 'description' => 'Equipment, studio, and experience tags', 'default' => true],
        'recommendation_rules' => ['label' => 'Add-on recommendations', 'description' => 'Suggest extra services such as a food stylist with a kitchen studio', 'default' => true],
        'cities' => ['label' => 'Egyptian governorates', 'description' => 'The 27 governorates used for vendor home location and travel fees', 'default' => true],
        'travel_fees' => ['label' => 'Out-of-governorate travel fees', 'description' => 'Vendors set transportation compensation when the shoot is outside their home governorate', 'default' => true],
        'maps' => ['label' => 'Map', 'description' => 'Mini map view in the app', 'default' => true],
        'bookings' => ['label' => 'Bookings', 'description' => 'Session bookings and calendars', 'default' => true],
        'escrow' => ['label' => 'Escrow wallet', 'description' => 'Hold funds until approval', 'default' => true],
        'payouts' => ['label' => 'Vendor payouts', 'description' => 'Release earnings after approval', 'default' => true],
        'commissions' => ['label' => 'Commissions', 'description' => 'Client fee and platform commission', 'default' => true],
        'cancellation_policies' => ['label' => 'Cancellation policies', 'description' => 'Cancellation tiers and penalties', 'default' => true],
        'disputes' => ['label' => 'Disputes', 'description' => 'Client and vendor complaints; admin reviews chat, booking, and payment then refunds, pays, or closes', 'default' => true],
        'revisions' => ['label' => 'Revision cycles', 'description' => 'Request edits on deliverables', 'default' => true],
        'protected_delivery' => ['label' => 'Protected delivery', 'description' => 'Watermarked preview before download', 'default' => true],
        'reviews' => ['label' => 'Star ratings & reviews', 'description' => 'Clients leave 1–5 stars after session approval; scores feed search ranking', 'default' => true],
        'badges' => ['label' => 'Incentive badges', 'description' => 'Top Rated / Popular from completion rate and reviews, with featured placement', 'default' => true],
        'portfolio' => ['label' => 'Portfolios', 'description' => 'Vendor photos and videos', 'default' => true],
        'chat' => ['label' => 'Chat', 'description' => 'In-app messaging', 'default' => true],
        'ai_assistant' => ['label' => 'AI assistant', 'description' => 'Gemini chat that asks location, date, and budget then compares vendors and helps the client pick', 'default' => true],
        'moodboard' => ['label' => 'Moodboard', 'description' => 'Generate a visual moodboard and script after payment', 'default' => true],
        'guest_mode' => ['label' => 'Guest mode', 'description' => 'Browse without signing up', 'default' => true],
        'replacement_workflow' => ['label' => 'Vendor replacement', 'description' => 'Offer substitutes on critical cancellations', 'default' => true],
        'app_intro' => ['label' => 'Intro screens', 'description' => 'Join as vendor / client / guest', 'default' => true],
        'cms' => ['label' => 'CMS pages', 'description' => 'Terms, privacy, and static pages', 'default' => true],
        'wallets' => ['label' => 'User wallets', 'description' => 'Client and vendor wallets for payments, coupons, refunds, earnings, and withdrawals', 'default' => true],
        'coupons' => ['label' => 'Coupons & offers', 'description' => 'Discount codes, seasonal offers, first-order and loyal-client deals, plus user / vendor / service targeting', 'default' => true],
        'notifications' => ['label' => 'Notifications', 'description' => 'In-app alerts for accounts, bookings, payments, chat, offers, commissions, and payouts', 'default' => true],
        'favorites' => ['label' => 'Favorites', 'description' => 'Clients save creators with the heart and reopen them from Favorites', 'default' => true],
        'smart_recommendations' => ['label' => 'Smart recommendations', 'description' => 'Learn from client bookings and searches to suggest similar vendors, nearby talent, budget fits, specialties, and matching offers', 'default' => true],
    ];

    public static function enabled(string $key): bool
    {
        $default = self::CATALOG[$key]['default'] ?? true;

        return (bool) Setting::getValue("features.{$key}", $default);
    }

    /**
     * @return array<string, bool>
     */
    public static function flags(): array
    {
        return collect(self::CATALOG)
            ->mapWithKeys(fn (array $item, string $key): array => [$key => self::enabled($key)])
            ->all();
    }

    public static function defaults(): array
    {
        return collect(self::CATALOG)
            ->mapWithKeys(fn (array $item, string $key) => [$key => $item['default']])
            ->all();
    }
}
