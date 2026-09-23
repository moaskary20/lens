<?php

namespace App\Filament\Resources\DeliverableResource\Pages;

use App\Filament\Resources\DeliverableResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDeliverable extends EditRecord
{
    protected static string $resource = DeliverableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
