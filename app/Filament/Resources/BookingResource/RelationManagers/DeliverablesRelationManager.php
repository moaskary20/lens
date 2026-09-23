<?php

namespace App\Filament\Resources\BookingResource\RelationManagers;

use App\Filament\Resources\DeliverableResource;
use Filament\Resources\RelationManagers\RelationManager;

class DeliverablesRelationManager extends RelationManager
{
    protected static string $relationship = 'deliverables';

    protected static ?string $relatedResource = DeliverableResource::class;

    protected static bool $isLazy = false;

    protected static ?string $title = 'Vendor delivery files';
}
