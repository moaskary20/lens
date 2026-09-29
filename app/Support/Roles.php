<?php

namespace App\Support;

use App\Models\Setting;
use App\Models\User;
use App\Models\VendorType;

class Roles
{
    public const CLIENT = 'client';

    public const VENDOR = 'vendor';

    public const ADMIN = 'admin';

    public const SUPERVISOR = 'supervisor';

    /**
     * @var array<string, string>
     */
    public const VENDOR_TYPE_FEATURES = [
        'photographer' => 'vendor_photographers',
        'videographer' => 'vendor_videographers',
        'reels' => 'vendor_reels',
        'studio' => 'vendor_studios',
        'model' => 'vendor_models',
        'ugc' => 'vendor_ugc',
        'food_stylist' => 'vendor_food_stylists',
    ];

    /**
     * @var array<string, string>
     */
    public const GROUPS = [
        'discovery' => 'Discovery',
        'studio' => 'Studio & profile',
        'bookings' => 'Bookings & delivery',
        'support' => 'Quality & support',
        'finance' => 'Wallet & finance',
        'intelligence' => 'AI & extras',
        'operations' => 'Staff operations',
        'platform' => 'Platform control',
    ];

    /**
     * @var array<string, array{label: string, description: string, panel: bool, capabilities: array<string, array{label: string, description: string, default: bool, group: string}>}>
     */
    public const CATALOG = [
        'client' => [
            'label' => 'Client (Customer)',
            'description' => 'Browses creators and studios, books sessions, chats, reports issues, reviews watermarked files, and approves payouts.',
            'panel' => false,
            'capabilities' => [
                'browse' => ['label' => 'Browse creators / studios', 'description' => 'Marketplace discovery', 'default' => true, 'group' => 'discovery'],
                'filter_equipment' => ['label' => 'Filter by equipment', 'description' => 'Camera, lenses, lighting, studio sets', 'default' => true, 'group' => 'discovery'],
                'filter_location' => ['label' => 'Filter by location', 'description' => 'Egyptian governorate and distance', 'default' => true, 'group' => 'discovery'],
                'filter_availability' => ['label' => 'Filter by availability', 'description' => 'Today, weekend, or a specific slot', 'default' => true, 'group' => 'discovery'],
                'view_map' => ['label' => 'Use the map', 'description' => 'Mini map of nearby creators in the app', 'default' => true, 'group' => 'discovery'],
                'save_favorites' => ['label' => 'Save favorites', 'description' => 'Heart creators and reopen them from Saved Creators', 'default' => true, 'group' => 'discovery'],
                'book_sessions' => ['label' => 'Book sessions', 'description' => 'Checkout into Lens escrow', 'default' => true, 'group' => 'bookings'],
                'review_watermarked' => ['label' => 'Review watermarked deliverables', 'description' => 'Protected previewer before approval', 'default' => true, 'group' => 'bookings'],
                'request_revisions' => ['label' => 'Request revisions', 'description' => 'Ask for edits over in-app chat', 'default' => true, 'group' => 'bookings'],
                'approve_payouts' => ['label' => 'Approve payouts', 'description' => 'Unlock originals and release vendor net', 'default' => true, 'group' => 'bookings'],
                'open_disputes' => ['label' => 'Open disputes', 'description' => 'File a dispute or complaint on a session; escrow stays held', 'default' => true, 'group' => 'support'],
                'report_issues' => ['label' => 'Report an issue', 'description' => 'Send Report an Issue from Profile to admin App issues', 'default' => true, 'group' => 'support'],
                'in_app_chat' => ['label' => 'In-app chat', 'description' => 'Message a creator about a brief or booking', 'default' => true, 'group' => 'support'],
                'leave_reviews' => ['label' => 'Leave star ratings', 'description' => 'Rate a session after approval', 'default' => true, 'group' => 'support'],
                'use_wallet' => ['label' => 'Use wallet and coupons', 'description' => 'Pay from wallet and apply promo codes at checkout', 'default' => true, 'group' => 'finance'],
                'receive_notifications' => ['label' => 'Receive notifications', 'description' => 'Booking, chat, payment, and support alerts', 'default' => true, 'group' => 'finance'],
                'use_ai_assistant' => ['label' => 'AI assistant', 'description' => 'Ask Lens to compare creators by date, location, and budget', 'default' => true, 'group' => 'intelligence'],
                'use_moodboard' => ['label' => 'Moodboard', 'description' => 'Generate a visual moodboard and script after payment', 'default' => true, 'group' => 'intelligence'],
            ],
        ],
        'vendor' => [
            'label' => 'Vendor (Creator / Studio)',
            'description' => 'Onboards under a vendor type, builds a portfolio, sets prices and travel fees, receives bookings, chats, and delivers assets.',
            'panel' => true,
            'capabilities' => [
                'onboard_by_type' => ['label' => 'Onboard under a vendor type', 'description' => 'Photographer, videographer, reels, studio, model, UGC, food stylist', 'default' => true, 'group' => 'studio'],
                'build_portfolio' => ['label' => 'Build a portfolio', 'description' => 'Photos, videos, and featured work', 'default' => true, 'group' => 'studio'],
                'gear_tags' => ['label' => 'Technical gear tags', 'description' => 'Equipment and specialty filters', 'default' => true, 'group' => 'studio'],
                'set_prices' => ['label' => 'Set own prices', 'description' => 'Fill amounts for the admin-defined pricing model of their type', 'default' => true, 'group' => 'studio'],
                'set_availability' => ['label' => 'Set calendar availability', 'description' => 'Open and blocked slots', 'default' => true, 'group' => 'studio'],
                'set_travel_fees' => ['label' => 'Set travel fees', 'description' => 'Out-of-governorate transportation compensation', 'default' => true, 'group' => 'studio'],
                'receive_bookings' => ['label' => 'Receive bookings', 'description' => 'Accept or reject incoming sessions', 'default' => true, 'group' => 'bookings'],
                'deliver_assets' => ['label' => 'Deliver completed assets', 'description' => 'Upload retouched files into the protected stream', 'default' => true, 'group' => 'bookings'],
                'open_disputes' => ['label' => 'Open disputes', 'description' => 'File a dispute or complaint on a session for admin review', 'default' => true, 'group' => 'bookings'],
                'in_app_chat' => ['label' => 'In-app chat', 'description' => 'Reply to client threads from the creator desk', 'default' => true, 'group' => 'bookings'],
                'use_wallet' => ['label' => 'View wallet', 'description' => 'Ledger of holds, earnings, coupons, and withdrawals', 'default' => true, 'group' => 'finance'],
                'request_payouts' => ['label' => 'Payments & earnings', 'description' => 'See payout status after client approval', 'default' => true, 'group' => 'finance'],
                'view_reviews' => ['label' => 'See ratings', 'description' => 'Client star ratings on completed sessions', 'default' => true, 'group' => 'support'],
                'report_issues' => ['label' => 'Report an issue', 'description' => 'Send app or payout problems to admin App issues', 'default' => true, 'group' => 'support'],
                'receive_notifications' => ['label' => 'Receive notifications', 'description' => 'Booking, chat, payout, and dispute alerts', 'default' => true, 'group' => 'support'],
            ],
        ],
        'supervisor' => [
            'label' => 'Platform supervisor',
            'description' => 'Staff role for verification, disputes, app issues, chat, escrow, and refund overrides. No access to platform or role settings.',
            'panel' => true,
            'capabilities' => [
                'view_operations' => ['label' => 'View operations', 'description' => 'Bookings, vendors, deliverables, favorites, reviews', 'default' => true, 'group' => 'operations'],
                'verify_vendors' => ['label' => 'Manage user verification', 'description' => 'Approve or reject vendor accounts', 'default' => true, 'group' => 'operations'],
                'view_chat' => ['label' => 'Review conversation threads', 'description' => 'Read client–creator chat when handling a case', 'default' => true, 'group' => 'operations'],
                'view_notifications' => ['label' => 'View notification log', 'description' => 'See what Lens pushed to clients, vendors, and staff', 'default' => true, 'group' => 'operations'],
                'triage_issues' => ['label' => 'Triage app issues', 'description' => 'Work Report an Issue tickets under App issues', 'default' => true, 'group' => 'support'],
                'resolve_disputes' => ['label' => 'Oversee dispute resolutions', 'description' => 'Review chat, booking, and payment then refund, pay the vendor, split, or close', 'default' => true, 'group' => 'support'],
                'refund_overrides' => ['label' => 'Handle refund overrides', 'description' => 'Manual full refund outside the policy table', 'default' => true, 'group' => 'finance'],
                'policy_exceptions' => ['label' => 'Enforce policy exceptions', 'description' => 'Apply cancellation workflows and penalties', 'default' => true, 'group' => 'finance'],
                'manage_escrow' => ['label' => 'Manage escrow and wallets', 'description' => 'Escrow ledger, user wallets, and vendor payouts', 'default' => true, 'group' => 'finance'],
                'manage_settings' => ['label' => 'Change platform settings', 'description' => 'Fees, features, search, delivery — admin only by default', 'default' => false, 'group' => 'platform'],
                'manage_roles' => ['label' => 'Change role capabilities', 'description' => 'Reserved for platform admin', 'default' => false, 'group' => 'platform'],
            ],
        ],
        'admin' => [
            'label' => 'Platform admin',
            'description' => 'Full control of users, marketplace config, finance, support desks, and role capabilities.',
            'panel' => true,
            'capabilities' => [
                'view_operations' => ['label' => 'View operations', 'description' => 'Always on for admins', 'default' => true, 'group' => 'operations'],
                'verify_vendors' => ['label' => 'Manage user verification', 'description' => 'Always on for admins', 'default' => true, 'group' => 'operations'],
                'view_chat' => ['label' => 'Review conversation threads', 'description' => 'Always on for admins', 'default' => true, 'group' => 'operations'],
                'view_notifications' => ['label' => 'View notification log', 'description' => 'Always on for admins', 'default' => true, 'group' => 'operations'],
                'triage_issues' => ['label' => 'Triage app issues', 'description' => 'Always on for admins', 'default' => true, 'group' => 'support'],
                'resolve_disputes' => ['label' => 'Oversee dispute resolutions', 'description' => 'Always on for admins', 'default' => true, 'group' => 'support'],
                'refund_overrides' => ['label' => 'Handle refund overrides', 'description' => 'Always on for admins', 'default' => true, 'group' => 'finance'],
                'policy_exceptions' => ['label' => 'Enforce policy exceptions', 'description' => 'Always on for admins', 'default' => true, 'group' => 'finance'],
                'manage_escrow' => ['label' => 'Manage escrow and wallets', 'description' => 'Always on for admins', 'default' => true, 'group' => 'finance'],
                'manage_settings' => ['label' => 'Change platform settings', 'description' => 'Always on for admins', 'default' => true, 'group' => 'platform'],
                'manage_roles' => ['label' => 'Change role capabilities', 'description' => 'Always on for admins', 'default' => true, 'group' => 'platform'],
            ],
        ],
    ];

    /**
     * Feature flags that a signed-in role must also be allowed to use.
     *
     * @var array<string, string>
     */
    public const CLIENT_FEATURE_GATES = [
        'bookings' => 'book_sessions',
        'maps' => 'view_map',
        'favorites' => 'save_favorites',
        'protected_delivery' => 'review_watermarked',
        'revisions' => 'request_revisions',
        'payouts' => 'approve_payouts',
        'disputes' => 'open_disputes',
        'issue_reports' => 'report_issues',
        'chat' => 'in_app_chat',
        'reviews' => 'leave_reviews',
        'wallets' => 'use_wallet',
        'coupons' => 'use_wallet',
        'notifications' => 'receive_notifications',
        'ai_assistant' => 'use_ai_assistant',
        'moodboard' => 'use_moodboard',
    ];

    /**
     * @var array<string, string>
     */
    public const VENDOR_FEATURE_GATES = [
        'portfolio' => 'build_portfolio',
        'filters' => 'gear_tags',
        'travel_fees' => 'set_travel_fees',
        'bookings' => 'receive_bookings',
        'disputes' => 'open_disputes',
        'chat' => 'in_app_chat',
        'wallets' => 'use_wallet',
        'payouts' => 'request_payouts',
        'reviews' => 'view_reviews',
        'issue_reports' => 'report_issues',
        'notifications' => 'receive_notifications',
    ];

    /**
     * @return array<string, array<string, array{label: string, description: string, default: bool, group: string}>>
     */
    public static function groupedCatalog(string $role): array
    {
        $grouped = [];

        foreach (self::CATALOG[$role]['capabilities'] ?? [] as $key => $capability) {
            $group = $capability['group'] ?? 'bookings';
            $grouped[$group][$key] = $capability;
        }

        $ordered = [];
        foreach (array_keys(self::GROUPS) as $group) {
            if (isset($grouped[$group])) {
                $ordered[$group] = $grouped[$group];
            }
        }

        foreach ($grouped as $group => $capabilities) {
            if (! isset($ordered[$group])) {
                $ordered[$group] = $capabilities;
            }
        }

        return $ordered;
    }

    /**
     * @return array<string, bool>
     */
    public static function capabilitiesFor(string $role): array
    {
        $defaults = collect(self::CATALOG[$role]['capabilities'] ?? [])
            ->mapWithKeys(fn (array $capability, string $key) => [$key => $capability['default']])
            ->all();

        $stored = Setting::getValue("roles.{$role}", []);
        $merged = array_merge($defaults, is_array($stored) ? $stored : []);

        return collect($defaults)
            ->mapWithKeys(fn (bool $default, string $key): array => [$key => (bool) ($merged[$key] ?? $default)])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $incoming
     */
    public static function persist(string $role, array $incoming): void
    {
        $normalized = collect(self::CATALOG[$role]['capabilities'] ?? [])
            ->mapWithKeys(fn (array $capability, string $key): array => [
                $key => (bool) ($incoming[$key] ?? $capability['default']),
            ])
            ->all();

        Setting::setValue("roles.{$role}", $normalized);
    }

    public static function can(string $role, string $capability): bool
    {
        return (bool) (self::capabilitiesFor($role)[$capability] ?? false);
    }

    public static function staffCan(?User $user, string $capability): bool
    {
        if (! $user?->is_active) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isSupervisor() && self::can(self::SUPERVISOR, $capability);
    }

    public static function roleCan(?User $user, string $capability): bool
    {
        if (! $user?->is_active) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return self::can($user->role, $capability);
    }

    public static function abortUnlessCan(?User $user, string $capability, string $message): void
    {
        abort_unless((bool) $user?->roleCan($capability), 403, $message);
    }

    /**
     * Feature flags for the app, narrowed by the signed-in role.
     *
     * @return array<string, bool>
     */
    public static function appFeatures(?User $user): array
    {
        $features = Feature::flags();
        if (! $user || $user->isStaff()) {
            return $features;
        }

        $gates = $user->isVendor() ? self::VENDOR_FEATURE_GATES : self::CLIENT_FEATURE_GATES;
        foreach ($gates as $feature => $capability) {
            if (array_key_exists($feature, $features)) {
                $features[$feature] = $features[$feature] && $user->roleCan($capability);
            }
        }

        if ($user->isClient()) {
            $features['filters'] = ($features['filters'] ?? false) && (
                $user->roleCan('filter_equipment')
                || $user->roleCan('filter_location')
                || $user->roleCan('filter_availability')
            );
        }

        return $features;
    }

    /**
     * @return array<int, string>
     */
    public static function enabledVendorTypeSlugs(): array
    {
        return collect(self::VENDOR_TYPE_FEATURES)
            ->filter(fn (string $feature): bool => Feature::enabled($feature))
            ->keys()
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return collect(self::CATALOG)
            ->mapWithKeys(fn (array $meta, string $role) => [
                $role => collect($meta['capabilities'])
                    ->mapWithKeys(fn (array $capability, string $key) => [$key => $capability['default']])
                    ->all(),
            ])
            ->all();
    }

    /**
     * Config the future app/API should consume.
     *
     * @return array<string, mixed>
     */
    public static function blueprint(): array
    {
        return [
            'roles' => collect(self::CATALOG)
                ->map(fn (array $meta, string $role): array => [
                    'key' => $role,
                    'label' => $meta['label'],
                    'description' => $meta['description'],
                    'panel' => $meta['panel'],
                    'capabilities' => self::capabilitiesFor($role),
                ])
                ->all(),
            'vendor_types' => VendorType::query()
                ->marketplace()
                ->orderBy('sort_order')
                ->get(['slug', 'name_en', 'name_ar', 'pricing_model', 'escrow_on_checkin']),
        ];
    }
}
