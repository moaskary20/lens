<?php

namespace App\Filament\Resources\WalletResource\Pages;

use App\Filament\Resources\WalletResource;
use Filament\Resources\Pages\EditRecord;

class EditWallet extends EditRecord
{
    protected static string $resource = WalletResource::class;

    /**
     * @return array<int, \Filament\Actions\Action>
     */
    protected function getFormActions(): array
    {
        return [];
    }
}
