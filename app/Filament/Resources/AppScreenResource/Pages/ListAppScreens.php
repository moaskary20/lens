<?php

namespace App\Filament\Resources\AppScreenResource\Pages;

use App\Filament\Resources\AppScreenResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAppScreens extends ListRecords
{
    protected static string $resource = AppScreenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
