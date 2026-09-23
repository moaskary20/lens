<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\Coupon;
use App\Models\Setting;
use App\Models\User;
use App\Models\Vendor;
use App\Notifications\LensAlert;

class LensNotifier
{
    public const ACCOUNT_REGISTERED = 'account_registered';

    public const ACCOUNT_APPROVED = 'account_approved';

    public const ACCOUNT_REJECTED = 'account_rejected';

    public const BOOKING_CREATED = 'booking_created';

    public const BOOKING_ACCEPTED = 'booking_accepted';

    public const PAYMENT = 'payment';

    public const MESSAGE = 'message';

    public const BOOKING_STATUS = 'booking_status';

    public const BOOKING_CANCELLED = 'booking_cancelled';

    public const REVIEW = 'review';

    public const OFFER = 'offer';

    public const COMMISSION = 'commission';

    public const PAYOUT = 'payout_transfer';

    public const APP_NOTICE = 'app_notice';

    protected static bool $muted = false;

    /**
     * @var array<string, array{label: string, description: string, default: bool}>
     */
    public const EVENTS = [
        self::ACCOUNT_REGISTERED => ['label' => 'New account', 'description' => 'Staff are told when a client or vendor registers', 'default' => true],
        self::ACCOUNT_APPROVED => ['label' => 'Account approved', 'description' => 'Vendor is told when verification is accepted', 'default' => true],
        self::ACCOUNT_REJECTED => ['label' => 'Account rejected', 'description' => 'Vendor is told when verification is rejected', 'default' => true],
        self::BOOKING_CREATED => ['label' => 'New request', 'description' => 'Vendor and staff are told when a client sends a booking', 'default' => true],
        self::BOOKING_ACCEPTED => ['label' => 'Request accepted', 'description' => 'Client is told when the vendor accepts', 'default' => true],
        self::PAYMENT => ['label' => 'Payment', 'description' => 'Client and vendor are told when checkout is held in escrow', 'default' => true],
        self::MESSAGE => ['label' => 'New message', 'description' => 'The other party is told when chat is posted', 'default' => true],
        self::BOOKING_STATUS => ['label' => 'Booking status', 'description' => 'Client and vendor are told when the session status changes', 'default' => true],
        self::BOOKING_CANCELLED => ['label' => 'Booking cancelled', 'description' => 'Both parties are told when a session is cancelled', 'default' => true],
        self::REVIEW => ['label' => 'Rating', 'description' => 'Vendor is told when a client leaves stars', 'default' => true],
        self::OFFER => ['label' => 'Offers', 'description' => 'Clients (and the vendor if targeted) are told about coupons and offers', 'default' => true],
        self::COMMISSION => ['label' => 'Commissions', 'description' => 'Vendor is told when platform commission is posted', 'default' => true],
        self::PAYOUT => ['label' => 'Money transfers', 'description' => 'Wallet top-ups, refunds, and payouts', 'default' => true],
        self::APP_NOTICE => ['label' => 'App notice', 'description' => 'Manual alerts staff send into the client app inbox', 'default' => true],
    ];

    public static function mute(): void
    {
        self::$muted = true;
    }

    public static function unmute(): void
    {
        self::$muted = false;
    }

    public static function muted(): bool
    {
        return self::$muted;
    }

    /**
     * @return array<string, bool>
     */
    public static function defaults(): array
    {
        return collect(self::EVENTS)
            ->mapWithKeys(fn (array $item, string $key) => [$key => $item['default']])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return collect(self::EVENTS)
            ->mapWithKeys(fn (array $item, string $key) => [$key => $item['label']])
            ->all();
    }

    public static function toUser(?User $user, string $event, string $title, string $body): void
    {
        if (self::$muted || ! $user || ! Feature::enabled('notifications') || ! self::eventEnabled($event)) {
            return;
        }

        $user->notify(new LensAlert($title, $body, $event));
    }

    public static function vendor(?Vendor $vendor, string $event, string $title, string $body): void
    {
        self::toUser($vendor?->user, $event, $title, $body);
    }

    public static function staff(string $event, string $title, string $body, ?int $exceptUserId = null): void
    {
        User::query()
            ->whereIn('role', ['admin', 'supervisor'])
            ->where('is_active', true)
            ->when($exceptUserId, fn ($query) => $query->where('id', '!=', $exceptUserId))
            ->each(fn (User $user) => self::toUser($user, $event, $title, $body));
    }

    public static function payment(Booking $booking): void
    {
        $booking->loadMissing(['client', 'vendor.user']);
        $amount = number_format((float) $booking->total_paid, 2).' '.Finance::currency();
        $body = $booking->reference.' · '.$amount.' held in escrow.';

        self::toUser($booking->client, self::PAYMENT, 'Payment received', 'Your payment for '.$body);
        self::vendor($booking->vendor, self::PAYMENT, 'Payment received', 'Client paid '.$body);
    }

    public static function announceOffer(Coupon $coupon): void
    {
        $coupon->loadMissing(['assignedUsers', 'user', 'vendor.user']);
        $label = $coupon->code.($coupon->label ? ' — '.$coupon->label : '');
        $body = $label.' is now available on Lens.';
        $ids = [];

        foreach ($coupon->assignedUsers as $user) {
            self::toUser($user, self::OFFER, 'New offer', $body);
            $ids[] = $user->id;
        }

        if ($coupon->user && ! in_array($coupon->user_id, $ids, true)) {
            self::toUser($coupon->user, self::OFFER, 'New offer', $body);
        }

        if ($coupon->vendor?->user) {
            self::vendor($coupon->vendor, self::OFFER, 'Offer on your profile', $body);
        }
    }

    /**
     * @param  list<User>|null  $users
     */
    public static function toClients(string $title, string $body, ?iterable $users = null): int
    {
        $query = $users === null
            ? User::query()->where('role', 'client')->where('is_active', true)
            : null;
        $targets = $query ? $query->get() : collect($users);
        $count = 0;

        foreach ($targets as $user) {
            if (! $user instanceof User) {
                continue;
            }
            self::toUser($user, self::APP_NOTICE, $title, $body);
            $count++;
        }

        return $count;
    }

    public static function eventEnabled(string $event): bool
    {
        $default = self::EVENTS[$event]['default'] ?? true;

        return (bool) Setting::getValue('notifications.'.$event, $default);
    }
}
