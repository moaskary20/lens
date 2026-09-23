<?php

namespace App\Support;

use App\Models\City;
use App\Models\Vendor;
use LogicException;

class Travel
{
    public static function isOutside(Vendor $vendor, ?City $destination): bool
    {
        $home = trim((string) $vendor->city?->governorate);
        $away = trim((string) $destination?->governorate);

        if ($home === '' || $away === '') {
            return false;
        }

        return strcasecmp($home, $away) !== 0;
    }

    public static function fee(Vendor $vendor, ?City $destination): float
    {
        if (! Feature::enabled('travel_fees') || ! $destination) {
            return 0.0;
        }

        if (! self::isOutside($vendor, $destination)) {
            return 0.0;
        }

        if (! $vendor->accepts_out_of_governorate) {
            throw new LogicException('This vendor does not travel outside their governorate.');
        }

        $vendor->loadMissing('travelRates');
        $governorate = trim((string) $destination->governorate);
        $specific = $vendor->travelRates
            ->first(fn ($rate): bool => strcasecmp((string) $rate->destination_governorate, $governorate) === 0);

        return round((float) ($specific?->fee ?? $vendor->default_travel_fee ?? 0), 2);
    }
}
