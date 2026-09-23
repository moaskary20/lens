<?php

namespace App\Filament\Resources\VendorAvailabilityResource\Pages;

use App\Filament\Resources\VendorAvailabilityResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditVendorAvailability extends EditRecord
{
    protected static string $resource = VendorAvailabilityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
