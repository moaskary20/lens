<?php

namespace App\Models;

use App\Support\Finance;
use App\Support\VendorNotifier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payout extends Model
{
    protected $fillable = [
        'vendor_id', 'booking_id', 'amount', 'status', 'method', 'reference', 'paid_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    protected static function booted(): void
    {
        static::created(function (Payout $payout): void {
            $payout->loadMissing('vendor.user');
            VendorNotifier::send(
                $payout->vendor,
                'Payout queued',
                number_format((float) $payout->amount, 2).' '.Finance::currency().' is waiting to be transferred.',
                \App\Support\LensNotifier::PAYOUT,
            );
        });

        static::updated(function (Payout $payout): void {
            if ($payout->wasChanged('status') && $payout->status === 'paid') {
                $payout->loadMissing('vendor.user');
                app(\App\Services\WalletService::class)->withdraw($payout);
                VendorNotifier::send(
                    $payout->vendor,
                    'Payout sent',
                    number_format((float) $payout->amount, 2).' '.Finance::currency().' was marked as paid.',
                    \App\Support\LensNotifier::PAYOUT,
                );
            }
        });
    }
}
