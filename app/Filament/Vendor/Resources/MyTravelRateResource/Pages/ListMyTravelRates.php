<?php

namespace App\Filament\Vendor\Resources\MyTravelRateResource\Pages;

use App\Filament\Vendor\Resources\MyTravelRateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMyTravelRates extends ListRecords
{
    protected static string $resource = MyTravelRateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Add destination fee'),
        ];
    }
}
