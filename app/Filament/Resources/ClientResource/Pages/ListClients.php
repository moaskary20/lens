<?php

namespace App\Filament\Resources\ClientResource\Pages;

use App\Filament\Resources\ClientResource;
use App\Models\User;
use App\Support\Roles;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListClients extends ListRecords
{
    protected static string $resource = ClientResource::class;

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        return [
            'active' => Tab::make('Active')
                ->badge(fn (): int => User::query()->where('role', Roles::CLIENT)->where('is_active', true)->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('is_active', true)),
            'not_active' => Tab::make('Not Active')
                ->badge(fn (): int => User::query()->where('role', Roles::CLIENT)->where('is_active', false)->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('is_active', false)),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Add client'),
        ];
    }
}
