<?php

namespace App\Filament\Resources\ReplacementOfferResource\Pages;

use App\Filament\Resources\ReplacementOfferResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditReplacementOffer extends EditRecord
{
    protected static string $resource = ReplacementOfferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
