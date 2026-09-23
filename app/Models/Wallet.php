<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    protected $fillable = [
        'user_id', 'available', 'pending', 'coupon_credit',
        'lifetime_earned', 'lifetime_withdrawn', 'lifetime_commission',
        'lifetime_paid', 'lifetime_refunded',
    ];

    protected function casts(): array
    {
        return [
            'available' => 'decimal:2',
            'pending' => 'decimal:2',
            'coupon_credit' => 'decimal:2',
            'lifetime_earned' => 'decimal:2',
            'lifetime_withdrawn' => 'decimal:2',
            'lifetime_commission' => 'decimal:2',
            'lifetime_paid' => 'decimal:2',
            'lifetime_refunded' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function totalEarnings(): float
    {
        return (float) $this->lifetime_earned;
    }

    public function spendable(): float
    {
        return round((float) $this->available + (float) $this->coupon_credit, 2);
    }
}
