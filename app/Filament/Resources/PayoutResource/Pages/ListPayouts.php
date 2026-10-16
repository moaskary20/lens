<?php

namespace App\Filament\Resources\PayoutResource\Pages;

use App\Filament\Resources\PayoutResource;
use App\Models\Payout;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListPayouts extends ListRecords
{
    protected static string $resource = PayoutResource::class;

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        $tabs = [
            'all' => Tab::make('All payouts')
                ->badge(fn (): int => Payout::query()->count()),
        ];

        foreach (PayoutResource::statuses() as $status => $label) {
            $tabs[$status] = Tab::make($label)
                ->badge(fn (): int => Payout::query()->where('status', $status)->count())
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
