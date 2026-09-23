<?php

namespace App\Filament\Resources\VendorResource\RelationManagers;

use App\Support\VendorProfile;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class AvailabilitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'availabilities';

    protected static ?string $title = 'Calendar availability';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return VendorProfile::calendarTitle($ownerRecord->vendorType?->slug);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            DateTimePicker::make('starts_at')->label('Starts')->required(),
            DateTimePicker::make('ends_at')->label('Ends')->required(),
            Select::make('status')->label('Status')->options([
                'open' => 'Open for booking',
                'blocked' => 'Blocked / unavailable',
                'booked' => 'Booked (locked)',
            ])->default('open')->required(),
            Textarea::make('notes')->label('Notes')->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('starts_at')
            ->columns([
                TextColumn::make('starts_at')->label('Starts')->dateTime('Y-m-d H:i'),
                TextColumn::make('ends_at')->label('Ends')->dateTime('Y-m-d H:i'),
                TextColumn::make('status')->label('Status')->badge(),
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
