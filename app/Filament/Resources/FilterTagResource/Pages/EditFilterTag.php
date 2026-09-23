<?php

namespace App\Filament\Resources\FilterTagResource\Pages;

use App\Filament\Resources\FilterTagResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFilterTag extends EditRecord
{
    protected static string $resource = FilterTagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
