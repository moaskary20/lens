<?php

namespace App\Models;

use App\Services\ReputationService;
use App\Support\Reputation;
use App\Support\VendorNotifier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class Review extends Model
{
    protected $fillable = [
        'booking_id', 'client_id', 'vendor_id', 'rating', 'comment', 'is_visible',
    ];

    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    protected static function booted(): void
    {
        static::creating(function (Review $review): void {
            $booking = $review->booking_id
                ? Booking::query()->find($review->booking_id)
                : $review->booking;

            if (! $booking) {
                return;
            }

            if (Reputation::settings()['reviews_only_after_approval']
                && ! in_array($booking->status, ['approved', 'completed'], true)) {
                throw new LogicException('Clients can rate a session only after they approve it.');
            }
        });

        $refresh = function (Review $review): void {
            if ($review->vendor) {
                app(ReputationService::class)->refresh($review->vendor);
            }
        };

        static::created(function (Review $review): void {
            $review->loadMissing(['vendor.user', 'client']);
            VendorNotifier::send(
                $review->vendor,
                'New star rating',
                ($review->client?->name ?: 'A client').' rated you '.$review->rating.'/5.',
                \App\Support\LensNotifier::REVIEW,
            );
        });

        static::saved($refresh);
        static::deleted($refresh);
    }
}
