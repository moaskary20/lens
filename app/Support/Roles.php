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
     * @var array<string, array{label: string, description: string, panel: bool, capabilities: array<string, array{label: string, description: string, default: bool}>}>
     */
    public const CATALOG = [
        'client' => [
            'label' => 'Client (Customer)',
            'description' => 'Browses creators and studios, books sessions, reviews watermarked files, requests edits, and approves payouts.',
            'panel' => false,
            'capabilities' => [
                'browse' => ['label' => 'Browse creators / studios', 'description' => 'Marketplace discovery', 'default' => true],
                'filter_equipment' => ['label' => 'Filter by equipment', 'description' => 'Camera, lenses, lighting, studio sets', 'default' => true],
                'filter_location' => ['label' => 'Filter by location', 'description' => 'Egyptian governorate and distance', 'default' => true],
                'filter_availability' => ['label' => 'Filter by availability', 'description' => 'Today, weekend, or a specific slot', 'default' => true],
                'book_sessions' => ['label' => 'Book sessions', 'description' => 'Checkout into Lens escrow', 'default' => true],
                'review_watermarked' => ['label' => 'Review watermarked deliverables', 'description' => 'Protected previewer before approval', 'default' => true],
                'request_revisions' => ['label' => 'Request revisions', 'description' => 'Ask for edits over in-app chat', 'default' => true],
                'approve_payouts' => ['label' => 'Approve payouts', 'description' => 'Unlock originals and release vendor net', 'default' => true],
                'open_disputes' => ['label' => 'Open disputes', 'description' => 'File a dispute or complaint on a session', 'default' => true],
            ],
        ],
        'vendor' => [
            'label' => 'Vendor (Creator / Studio)',
            'description' => 'Onboards under a vendor type, builds a tagged portfolio, sets calendar availability, receives bookings, and delivers assets.',
            'panel' => true,
            'capabilities' => [
                'onboard_by_type' => ['label' => 'Onboard under a vendor type', 'description' => 'Photographer, videographer, reels, studio, model, UGC, food stylist', 'default' => true],
                'build_portfolio' => ['label' => 'Build a portfolio', 'description' => 'Photos, videos, and featured work', 'default' => true],
                'gear_tags' => ['label' => 'Technical gear tags', 'description' => 'Equipment and specialty filters', 'default' => true],
                'set_prices' => ['label' => 'Set own prices', 'description' => 'Fill amounts for the admin-defined pricing model of their type', 'default' => true],
                'set_availability' => ['label' => 'Set calendar availability', 'description' => 'Open and blocked slots', 'default' => true],
                'receive_bookings' => ['label' => 'Receive bookings', 'description' => 'Accept or reject incoming sessions', 'default' => true],
                'deliver_assets' => ['label' => 'Deliver completed assets', 'description' => 'Upload retouched files into the protected stream', 'default' => true],
                'open_disputes' => ['label' => 'Open disputes', 'description' => 'File a dispute or complaint on a session for admin review', 'default' => true],
            ],
        ],
        'supervisor' => [
            'label' => 'Platform supervisor',
            'description' => 'Staff role for verification, disputes, policy exceptions, and refund overrides. No access to platform settings.',
            'panel' => true,
            'capabilities' => [
                'view_operations' => ['label' => 'View operations', 'description' => 'Bookings, vendors, deliverables, chat, payouts', 'default' => true],
                'verify_vendors' => ['label' => 'Manage user verification', 'description' => 'Approve or reject vendor accounts', 'default' => true],
                'resolve_disputes' => ['label' => 'Oversee dispute resolutions', 'description' => 'Review chat, booking, and payment then refund, pay the vendor, split, or close', 'default' => true],
                'refund_overrides' => ['label' => 'Handle refund overrides', 'description' => 'Manual full refund outside the policy table', 'default' => true],
                'policy_exceptions' => ['label' => 'Enforce policy exceptions', 'description' => 'Apply cancellation and replacement workflows', 'default' => true],
                'manage_settings' => ['label' => 'Change platform settings', 'description' => 'Fees, features, search, delivery — admin only by default', 'default' => false],
                'manage_roles' => ['label' => 'Change role capabilities', 'description' => 'Reserved for platform admin', 'default' => false],
            ],
        ],
        'admin' => [
            'label' => 'Platform admin',
            'description' => 'Full control of users, marketplace config, finance, and role capabilities.',
            'panel' => true,
            'capabilities' => [
                'view_operations' => ['label' => 'View operations', 'description' => 'Always on for admins', 'default' => true],
                'verify_vendors' => ['label' => 'Manage user verification', 'description' => 'Always on for admins', 'default' => true],
                'resolve_disputes' => ['label' => 'Oversee dispute resolutions', 'description' => 'Always on for admins', 'default' => true],
                'refund_overrides' => ['label' => 'Handle refund overrides', 'description' => 'Always on for admins', 'default' => true],
                'policy_exceptions' => ['label' => 'Enforce policy exceptions', 'description' => 'Always on for admins', 'default' => true],
                'manage_settings' => ['label' => 'Change platform settings', 'description' => 'Always on for admins', 'default' => true],
                'manage_roles' => ['label' => 'Change role capabilities', 'description' => 'Always on for admins', 'default' => true],
            ],
        ],
    ];

    /**
     * @return array<string, bool>
     */
    public static function capabilitiesFor(string $role): array
    {
        $defaults = collect(self::CATALOG[$role]['capabilities'] ?? [])
            ->mapWithKeys(fn (array $capability, string $key) => [$key => $capability['default']])
            ->all();

        $stored = Setting::getValue("roles.{$role}", []);

        return array_merge($defaults, is_array($stored) ? $stored : []);
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
