<?php

namespace App\Filament\Vendor\Resources\MyTravelRateResource\Pages;

use App\Filament\Vendor\Resources\MyTravelRateResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMyTravelRate extends CreateRecord
{
    protected static string $resource = MyTravelRateResource::class;

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
