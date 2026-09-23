<?php

namespace App\Filament\Vendor\Resources\MyTravelRateResource\Pages;

use App\Filament\Vendor\Resources\MyTravelRateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMyTravelRate extends EditRecord
{
    protected static string $resource = MyTravelRateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['vendor_id'] = auth()->user()?->vendor?->id;

        return $data;
    }
}
