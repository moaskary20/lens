<?php

namespace App\Filament\Resources\WalletResource\Pages;

use App\Filament\Resources\WalletResource;
use App\Models\Wallet;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListWallets extends ListRecords
{
    protected static string $resource = WalletResource::class;

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All')
                ->badge(fn (): int => Wallet::query()->count()),
            'vendor' => Tab::make('Vendor')
                ->badge(fn (): int => Wallet::query()->whereHas('user', fn (Builder $query): Builder => $query->where('role', 'vendor'))->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereHas('user', fn (Builder $inner): Builder => $inner->where('role', 'vendor'))),
            'client' => Tab::make('Client')
                ->badge(fn (): int => Wallet::query()->whereHas('user', fn (Builder $query): Builder => $query->where('role', 'client'))->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereHas('user', fn (Builder $inner): Builder => $inner->where('role', 'client'))),
        ];
    }
}
