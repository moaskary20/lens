<?php

namespace App\Support;

use App\Models\Vendor;

class VendorNotifier
{
    public static function send(?Vendor $vendor, string $title, string $body, string $event = LensNotifier::BOOKING_STATUS): void
    {
        LensNotifier::vendor($vendor, $event, $title, $body);
    }
}
