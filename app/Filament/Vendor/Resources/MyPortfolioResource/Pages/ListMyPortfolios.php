<?php

namespace App\Filament\Vendor\Resources\MyPortfolioResource\Pages;

use App\Filament\Vendor\Resources\MyPortfolioResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMyPortfolios extends ListRecords
{
    protected static string $resource = MyPortfolioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Add previous project'),
        ];
    }
}
