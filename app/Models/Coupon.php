<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    public const CAMPAIGN_COUPON = 'coupon';

    public const CAMPAIGN_SEASONAL = 'seasonal';

    public const CAMPAIGN_USER = 'user';

    public const CAMPAIGN_VENDOR = 'vendor';

    public const CAMPAIGN_SERVICE = 'service';

    public const CAMPAIGN_FIRST_ORDER = 'first_order';

    public const CAMPAIGN_LOYAL = 'loyal';

    public const TYPE_WALLET = 'wallet_credit';

    public const TYPE_PERCENT = 'percent';

    public const TYPE_AMOUNT = 'amount';

    protected $fillable = [
        'code', 'label', 'campaign', 'type', 'amount', 'user_id', 'vendor_id', 'vendor_type_id',
        'max_uses', 'uses_count', 'starts_at', 'expires_at', 'min_completed_bookings',
        'max_uses_per_user', 'is_active', 'auto_apply', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
            'auto_apply' => 'boolean',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function campaigns(): array
    {
        return [
            self::CAMPAIGN_COUPON => 'Discount coupon',
            self::CAMPAIGN_SEASONAL => 'Seasonal offer',
            self::CAMPAIGN_USER => 'Specific clients',
            self::CAMPAIGN_VENDOR => 'Specific vendor',
            self::CAMPAIGN_SERVICE => 'Service type',
            self::CAMPAIGN_FIRST_ORDER => 'First order',
            self::CAMPAIGN_LOYAL => 'Loyal clients',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function rewardTypes(): array
    {
        return [
            self::TYPE_WALLET => 'Wallet credit',
            self::TYPE_PERCENT => 'Percent off session',
            self::TYPE_AMOUNT => 'Fixed amount off checkout',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'coupon_user');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function vendorType(): BelongsTo
    {
        return $this->belongsTo(VendorType::class);
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function isWalletCredit(): bool
    {
        return in_array($this->type, [self::TYPE_WALLET, 'fixed'], true);
    }

    public function isCheckoutDiscount(): bool
    {
        return in_array($this->type, [self::TYPE_PERCENT, self::TYPE_AMOUNT], true);
    }

    public function isLive(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->max_uses !== null && $this->uses_count >= $this->max_uses) {
            return false;
        }

        return true;
    }

    public function isRedeemable(?User $user = null): bool
    {
        if (! $this->isWalletCredit() || ! $this->isLive() || ! $user) {
            return false;
        }

        return $this->appliesTo($user);
    }

    public function appliesTo(?User $user, ?Booking $booking = null, ?Vendor $vendor = null): bool
    {
        if (! $this->isLive()) {
            return false;
        }

        $vendor ??= $booking?->vendor;

        if (! $this->matchesUser($user)) {
            return false;
        }

        if (! $this->matchesVendor($vendor)) {
            return false;
        }

        if (! $this->matchesCampaign($user, $vendor, $booking)) {
            return false;
        }

        if ($user && $this->reachedUserLimit($user, $booking)) {
            return false;
        }

        return true;
    }

    /**
     * @return list<int>
     */
    public function targetUserIds(): array
    {
        $ids = $this->assignedUsers->pluck('id')->all();
        if ($this->user_id) {
            $ids[] = (int) $this->user_id;
        }

        return array_values(array_unique(array_map('intval', $ids)));
    }

    public function discountAmount(float $sessionPrice, float $totalPaid): float
    {
        if ($this->isWalletCredit()) {
            return 0.0;
        }

        $raw = $this->type === self::TYPE_PERCENT
            ? round($sessionPrice * ((float) $this->amount / 100), 2)
            : round((float) $this->amount, 2);

        return max(0, min($raw, $totalPaid));
    }

    protected function matchesUser(?User $user): bool
    {
        $ids = $this->targetUserIds();
        if ($ids === []) {
            return true;
        }

        return $user !== null && in_array((int) $user->id, $ids, true);
    }

    protected function matchesVendor(?Vendor $vendor): bool
    {
        if ($this->vendor_id && (! $vendor || (int) $vendor->id !== (int) $this->vendor_id)) {
            return false;
        }

        if ($this->vendor_type_id && (! $vendor || (int) $vendor->vendor_type_id !== (int) $this->vendor_type_id)) {
            return false;
        }

        return true;
    }

    protected function matchesCampaign(?User $user, ?Vendor $vendor, ?Booking $booking): bool
    {
        return match ($this->campaign ?: self::CAMPAIGN_COUPON) {
            self::CAMPAIGN_USER => $this->targetUserIds() !== [] && $this->matchesUser($user),
            self::CAMPAIGN_VENDOR => $vendor !== null && (int) $vendor->id === (int) $this->vendor_id,
            self::CAMPAIGN_SERVICE => $vendor !== null && (int) $vendor->vendor_type_id === (int) $this->vendor_type_id,
            self::CAMPAIGN_FIRST_ORDER => $user !== null && $this->activeBookingCount($user, $booking) === 0,
            self::CAMPAIGN_LOYAL => $user !== null && $this->completedBookingCount($user) >= (int) $this->min_completed_bookings,
            default => true,
        };
    }

    protected function reachedUserLimit(User $user, ?Booking $booking): bool
    {
        $limit = (int) ($this->max_uses_per_user ?: 1);
        $used = Booking::query()
            ->where('coupon_id', $this->id)
            ->where('client_id', $user->id)
            ->when($booking?->id, fn ($query) => $query->where('id', '!=', $booking->id))
            ->count();

        $used += WalletTransaction::query()
            ->where('coupon_id', $this->id)
            ->whereHas('wallet', fn ($query) => $query->where('user_id', $user->id))
            ->count();

        return $used >= $limit;
    }

    protected function activeBookingCount(User $user, ?Booking $except): int
    {
        return Booking::query()
            ->where('client_id', $user->id)
            ->when($except?->id, fn ($query) => $query->where('id', '!=', $except->id))
            ->whereNotIn('status', ['cancelled', 'rejected', 'failed', 'refunded'])
            ->count();
    }

    protected function completedBookingCount(User $user): int
    {
        return Booking::query()
            ->where('client_id', $user->id)
            ->whereIn('status', ['approved', 'completed'])
            ->count();
    }
}
