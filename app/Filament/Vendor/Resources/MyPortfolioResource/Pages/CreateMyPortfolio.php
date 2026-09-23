<?php

namespace App\Filament\Vendor\Resources\MyPortfolioResource\Pages;

use App\Filament\Vendor\Resources\MyPortfolioResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMyPortfolio extends CreateRecord
{
    protected static string $resource = MyPortfolioResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['vendor_id'] = auth()->user()?->vendor?->id;
        $data['path'] = filled($data['path'] ?? null) ? $data['path'] : '';

        return $data;
    }
}
