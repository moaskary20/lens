<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        $tabs = [
            'all' => Tab::make('All users')
                ->badge(fn (): int => User::query()->count()),
        ];

        foreach (UserResource::roleOptions() as $role => $label) {
            $tabs[$role] = Tab::make($label)
                ->badge(fn (): int => User::query()->where('role', $role)->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('role', $role));
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
