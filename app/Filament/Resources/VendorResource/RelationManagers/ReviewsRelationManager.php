<?php

namespace App\Filament\Resources\VendorResource\RelationManagers;

use App\Filament\Resources\ReviewResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ReviewsRelationManager extends RelationManager
{
    protected static string $relationship = 'reviews';

    protected static ?string $title = 'Star ratings';

    public function form(Schema $schema): Schema
    {
        return ReviewResource::form($schema);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('comment')
            ->columns([
                TextColumn::make('client.name')->label('Client'),
                TextColumn::make('booking.reference')->label('Session'),
                TextColumn::make('rating')->label('Stars')
                    ->formatStateUsing(fn (int $state): string => str_repeat('★', $state).str_repeat('☆', 5 - $state)),
                TextColumn::make('comment')->label('Review')->limit(40),
                IconColumn::make('is_visible')->label('Visible')->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
