<?php

namespace App\Filament\Resources\DeliverableResource\Pages;

use App\Filament\Resources\DeliverableResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDeliverables extends ListRecords
{
    protected static string $resource = DeliverableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
