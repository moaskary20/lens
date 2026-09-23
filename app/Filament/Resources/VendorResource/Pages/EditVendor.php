<?php

namespace App\Filament\Resources\VendorResource\Pages;

use App\Filament\Resources\VendorResource;
use App\Support\VendorProfile;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditVendor extends EditRecord
{
    protected static string $resource = VendorResource::class;

    /**
     * @var array<string, mixed>
     */
    protected array $profileFilters = [];

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
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['profile_filters'] = VendorProfile::hydrateProfileFilters($this->record);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->profileFilters = $data['profile_filters'] ?? [];
        unset($data['profile_filters']);

        return $data;
    }

    protected function afterSave(): void
    {
        $raw = $this->form->getRawState();
        VendorProfile::syncProfileFilters(
            $this->record,
            is_array($raw['profile_filters'] ?? null) ? $raw['profile_filters'] : $this->profileFilters,
            $this->record->filterTags()->pluck('filter_tags.id')->all(),
        );
    }
}
