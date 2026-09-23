<?php

namespace App\Models;

use App\Support\DeliveryProtection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Deliverable extends Model
{
    protected $fillable = [
        'booking_id', 'path', 'original_name', 'is_watermarked', 'is_unlocked', 'version',
    ];

    protected function casts(): array
    {
        return [
            'is_watermarked' => 'boolean',
            'is_unlocked' => 'boolean',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    protected static function booted(): void
    {
        static::saving(function (Deliverable $deliverable): void {
            if (! DeliveryProtection::downloadsLocked()) {
                return;
            }

            $booking = $deliverable->booking;
            $approved = $booking && in_array($booking->status, ['approved', 'completed'], true);

            if (! $approved) {
                $deliverable->is_unlocked = false;
            }
        });
    }
}
