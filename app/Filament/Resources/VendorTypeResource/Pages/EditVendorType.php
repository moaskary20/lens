<?php

namespace App\Filament\Resources\VendorTypeResource\Pages;

use App\Filament\Resources\VendorTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditVendorType extends EditRecord
{
    protected static string $resource = VendorTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
