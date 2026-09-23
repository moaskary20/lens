<?php

namespace App\Filament\Resources\VendorResource\Pages;

use App\Filament\Resources\VendorResource;
use App\Support\VendorProfile;
use Filament\Resources\Pages\CreateRecord;

class CreateVendor extends CreateRecord
{
    protected static string $resource = VendorResource::class;

    /**
     * @var array<string, mixed>
     */
    protected array $profileFilters = [];

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->profileFilters = $data['profile_filters'] ?? [];
        unset($data['profile_filters']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $raw = $this->form->getRawState();
        VendorProfile::syncProfileFilters(
            $this->record,
            is_array($raw['profile_filters'] ?? null) ? $raw['profile_filters'] : $this->profileFilters,
            $this->record->filterTags()->pluck('filter_tags.id')->all(),
        );
    }
}
