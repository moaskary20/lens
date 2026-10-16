<?php

namespace App\Filament\Resources\DisputeResource\Pages;

use App\Filament\Resources\DisputeResource;
use App\Models\Dispute;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListDisputes extends ListRecords
{
    protected static string $resource = DisputeResource::class;

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        $tabs = [
            'all' => Tab::make('All disputes')
                ->badge(fn (): int => Dispute::query()->count()),
        ];

        foreach (DisputeResource::statuses() as $status => $label) {
            $tabs[$status] = Tab::make($label)
                ->badge(fn (): int => Dispute::query()->where('status', $status)->count())
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
