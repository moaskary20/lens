<?php

namespace App\Filament\Resources\FilterTagResource\Pages;

use App\Filament\Resources\FilterTagResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFilterTags extends ListRecords
{
    protected static string $resource = FilterTagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
