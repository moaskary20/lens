<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Review;
use App\Support\Feature;
use App\Support\Reputation;
use LogicException;

class ReviewService
{
    public function submit(Booking $booking, int $rating, ?string $comment = null, bool $visible = true): Review
    {
        if (! Feature::enabled('reviews')) {
            throw new LogicException('Star ratings are disabled.');
        }

        $booking->loadMissing('review');

        if ($booking->review) {
            throw new LogicException('This session already has a client review.');
        }

        if (Reputation::settings()['reviews_only_after_approval']
            && ! in_array($booking->status, ['approved', 'completed'], true)) {
            throw new LogicException('Clients can rate a session only after they approve it.');
        }

        $stars = max(1, min(5, $rating));

        return Review::query()->create([
            'booking_id' => $booking->id,
            'client_id' => $booking->client_id,
            'vendor_id' => $booking->vendor_id,
            'rating' => $stars,
            'comment' => $comment,
            'is_visible' => $visible,
        ]);
    }
}
