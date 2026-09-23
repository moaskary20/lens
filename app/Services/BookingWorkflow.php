<?php

namespace App\Services;

use App\Models\Booking;
use LogicException;

class BookingWorkflow
{
    public function accept(Booking $booking): Booking
    {
        if ($booking->status !== 'pending') {
            throw new LogicException('Only pending requests can be accepted.');
        }

        $booking->update(['status' => 'accepted']);
        app(ReputationService::class)->bump($booking->vendor, 'accepted_sessions');

        return $booking->fresh();
    }

    public function reject(Booking $booking): Booking
    {
        if ($booking->status !== 'pending') {
            throw new LogicException('Only pending requests can be rejected.');
        }

        $booking->update(['status' => 'rejected']);
        app(ReputationService::class)->bump($booking->vendor, 'rejected_sessions');

        return $booking->fresh();
    }
}
