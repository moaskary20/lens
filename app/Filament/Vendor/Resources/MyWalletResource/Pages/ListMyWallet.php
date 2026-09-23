<?php

namespace App\Filament\Vendor\Resources\MyWalletResource\Pages;

use App\Filament\Vendor\Resources\MyWalletResource;
use App\Filament\Vendor\Widgets\VendorWalletStats;
use Filament\Resources\Pages\ListRecords;

class ListMyWallet extends ListRecords
{
    protected static string $resource = MyWalletResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            VendorWalletStats::class,
        ];
    }
}
