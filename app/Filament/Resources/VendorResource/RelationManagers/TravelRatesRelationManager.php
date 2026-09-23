<?php

namespace App\Filament\Resources\VendorResource\RelationManagers;

use App\Support\Egypt;
use App\Support\Finance;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TravelRatesRelationManager extends RelationManager
{
    protected static string $relationship = 'travelRates';

    protected static ?string $title = 'Travel fees by destination governorate';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('destination_governorate')
                ->label('Destination governorate')
                ->options(Egypt::options())
                ->searchable()
                ->required(),
            TextInput::make('fee')->label('Transportation fee')->numeric()->prefix(Finance::currency())->required(),
            Textarea::make('notes')->label('Notes')->helperText('Fuel, tickets, or driver cost for this governorate.'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('destination_governorate')
            ->columns([
                TextColumn::make('destination_governorate')->label('Governorate'),
                TextColumn::make('fee')->label('Fee')->money(Finance::currency()),
                TextColumn::make('notes')->label('Notes')->limit(40),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
