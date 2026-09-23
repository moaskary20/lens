<?php

namespace App\Filament\Resources\VendorResource\RelationManagers;

use App\Filament\Resources\BookingResource;
use Filament\Resources\RelationManagers\RelationManager;

class BookingsRelationManager extends RelationManager
{
    protected static string $relationship = 'bookings';

    protected static ?string $relatedResource = BookingResource::class;

    protected static bool $isLazy = false;

    protected static ?string $title = 'Client projects';
}
