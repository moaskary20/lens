<?php

namespace App\Filament\Vendor\Resources\MyAvailabilityResource\Pages;

use App\Filament\Vendor\Resources\MyAvailabilityResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMyAvailabilities extends ListRecords
{
    protected static string $resource = MyAvailabilityResource::class;

    public function getTitle(): string
    {
        return MyAvailabilityResource::getNavigationLabel();
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Add slot'),
        ];
    }
}
