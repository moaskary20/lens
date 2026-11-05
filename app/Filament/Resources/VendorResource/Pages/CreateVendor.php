<?php

namespace App\Filament\Resources\VendorResource\Pages;

use App\Filament\Resources\VendorResource;
use App\Support\VendorAppProfile;
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
     * @var list<array<string, mixed>>
     */
    protected array $projectGroups = [];

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->profileFilters = $data['profile_filters'] ?? [];
        $this->projectGroups = is_array($data['project_groups'] ?? null) ? $data['project_groups'] : [];
        unset($data['profile_filters'], $data['project_groups']);

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
        VendorAppProfile::syncAdminProjectGroups(
            $this->record,
            is_array($raw['project_groups'] ?? null) ? $raw['project_groups'] : $this->projectGroups,
        );
    }
}
