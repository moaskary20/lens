<?php

namespace App\Filament\Vendor\Resources\MyPortfolioResource\Pages;

use App\Filament\Vendor\Resources\MyPortfolioResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMyPortfolio extends EditRecord
{
    protected static string $resource = MyPortfolioResource::class;

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
        $data['path'] = filled($data['path'] ?? null) ? $data['path'] : '';
        $data['vendor_id'] = auth()->user()?->vendor?->id;

        return $data;
    }
}
