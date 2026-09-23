<?php

namespace App\Filament\Resources\VendorTypeResource\Pages;

use App\Filament\Resources\VendorTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVendorTypes extends ListRecords
{
    protected static string $resource = VendorTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
