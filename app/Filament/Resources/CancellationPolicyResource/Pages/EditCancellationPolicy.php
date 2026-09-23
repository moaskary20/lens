<?php

namespace App\Filament\Resources\CancellationPolicyResource\Pages;

use App\Filament\Resources\CancellationPolicyResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCancellationPolicy extends EditRecord
{
    protected static string $resource = CancellationPolicyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
