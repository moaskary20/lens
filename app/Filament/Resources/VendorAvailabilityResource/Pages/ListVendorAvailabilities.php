<?php

namespace App\Filament\Resources\VendorAvailabilityResource\Pages;

use App\Filament\Resources\VendorAvailabilityResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVendorAvailabilities extends ListRecords
{
    protected static string $resource = VendorAvailabilityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
