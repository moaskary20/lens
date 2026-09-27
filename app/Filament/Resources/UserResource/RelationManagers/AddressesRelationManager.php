<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class AddressesRelationManager extends RelationManager
{
    protected static string $relationship = 'addresses';

    protected static ?string $title = 'Addresses';

    protected static bool $isLazy = false;

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord instanceof User && $ownerRecord->isClient();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('label')->label('Label')->default('Home')->required(),
            TextInput::make('line')->label('Address')->required()->maxLength(255),
            TextInput::make('city')->label('City'),
            TextInput::make('latitude')->label('Latitude')->numeric(),
            TextInput::make('longitude')->label('Longitude')->numeric(),
            Toggle::make('is_default')->label('Default'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('label')->label('Label'),
                TextColumn::make('line')->label('Address'),
                TextColumn::make('city')->label('City'),
                IconColumn::make('is_default')->label('Default')->boolean(),
            ])
            ->headerActions([
                CreateAction::make()->label('Add address'),
            ])
            ->recordActions([
                DeleteAction::make(),
            ]);
    }
}
