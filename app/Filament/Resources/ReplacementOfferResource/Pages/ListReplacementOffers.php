<?php

namespace App\Filament\Resources\ReplacementOfferResource\Pages;

use App\Filament\Resources\ReplacementOfferResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListReplacementOffers extends ListRecords
{
    protected static string $resource = ReplacementOfferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
