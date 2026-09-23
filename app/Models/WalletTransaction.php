<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends Model
{
    public const PAYMENT = 'payment';

    public const COUPON = 'coupon';

    public const REFUND = 'refund';

    public const EARNING_HOLD = 'earning_hold';

    public const HOLD_REVERSAL = 'hold_reversal';

    public const EARNING_RELEASE = 'earning_release';

    public const COMMISSION = 'commission';

    public const WITHDRAWAL = 'withdrawal';

    public const TOPUP = 'topup';

    protected $fillable = [
        'wallet_id', 'booking_id', 'payout_id', 'coupon_id', 'type', 'amount',
        'available_delta', 'pending_delta', 'coupon_delta', 'status', 'notes', 'processed_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'available_delta' => 'decimal:2',
            'pending_delta' => 'decimal:2',
            'coupon_delta' => 'decimal:2',
        ];
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function payout(): BelongsTo
    {
        return $this->belongsTo(Payout::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
