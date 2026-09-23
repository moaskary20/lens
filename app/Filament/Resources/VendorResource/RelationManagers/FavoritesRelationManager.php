<?php

namespace App\Filament\Resources\VendorResource\RelationManagers;

use Filament\Actions\DeleteAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FavoritesRelationManager extends RelationManager
{
    protected static string $relationship = 'favorites';

    protected static ?string $title = 'Saved by clients';

    protected static bool $isLazy = false;

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')->label('Client'),
                TextColumn::make('user.email')->label('Email'),
                TextColumn::make('created_at')->label('Saved')->since(),
            ])
            ->recordActions([
                DeleteAction::make()->label('Remove'),
            ]);
    }
}
