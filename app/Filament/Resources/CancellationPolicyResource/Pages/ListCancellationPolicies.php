<?php

namespace App\Filament\Resources\CancellationPolicyResource\Pages;

use App\Filament\Resources\CancellationPolicyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCancellationPolicies extends ListRecords
{
    protected static string $resource = CancellationPolicyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
