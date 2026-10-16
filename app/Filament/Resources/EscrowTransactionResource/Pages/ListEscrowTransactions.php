<?php

namespace App\Filament\Resources\EscrowTransactionResource\Pages;

use App\Filament\Resources\EscrowTransactionResource;
use App\Models\EscrowTransaction;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListEscrowTransactions extends ListRecords
{
    protected static string $resource = EscrowTransactionResource::class;

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        $tabs = [
            'all' => Tab::make('All transactions')
                ->badge(fn (): int => EscrowTransaction::query()->count()),
        ];

        foreach (EscrowTransactionResource::statuses() as $status => $label) {
            $tabs[$status] = Tab::make($label)
                ->badge(fn (): int => EscrowTransaction::query()->where('status', $status)->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', $status));
        }

        return $tabs;
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
