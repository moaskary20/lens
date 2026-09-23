<?php

namespace App\Filament\Vendor\Resources\MyAvailabilityResource\Pages;

use App\Filament\Vendor\Resources\MyAvailabilityResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMyAvailability extends EditRecord
{
    protected static string $resource = MyAvailabilityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn (): bool => $this->getRecord()->status !== 'booked'),
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
