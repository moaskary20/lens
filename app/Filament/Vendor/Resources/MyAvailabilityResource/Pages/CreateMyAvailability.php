<?php

namespace App\Filament\Vendor\Resources\MyAvailabilityResource\Pages;

use App\Filament\Vendor\Resources\MyAvailabilityResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMyAvailability extends CreateRecord
{
    protected static string $resource = MyAvailabilityResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['vendor_id'] = auth()->user()?->vendor?->id;

        return $data;
    }
}
