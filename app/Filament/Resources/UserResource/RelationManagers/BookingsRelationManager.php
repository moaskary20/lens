<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Filament\Resources\BookingResource;
use App\Models\User;
use Filament\Resources\RelationManagers\RelationManager;
use Illuminate\Database\Eloquent\Model;

class BookingsRelationManager extends RelationManager
{
    protected static string $relationship = 'bookings';

    protected static ?string $relatedResource = BookingResource::class;

    protected static bool $isLazy = false;

    protected static ?string $title = 'Client projects';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord instanceof User && $ownerRecord->isClient();
    }
}
