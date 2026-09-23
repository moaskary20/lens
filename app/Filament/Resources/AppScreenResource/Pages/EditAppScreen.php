<?php

namespace App\Filament\Resources\AppScreenResource\Pages;

use App\Filament\Resources\AppScreenResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAppScreen extends EditRecord
{
    protected static string $resource = AppScreenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
