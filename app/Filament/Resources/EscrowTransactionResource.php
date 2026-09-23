<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasOptionalFeature;
use App\Filament\Resources\EscrowTransactionResource\Pages;
use App\Models\EscrowTransaction;
use App\Support\Finance;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class EscrowTransactionResource extends Resource
{
    use HasOptionalFeature;

    protected static ?string $model = EscrowTransaction::class;

    protected static ?string $featureKey = 'escrow';

    protected static ?string $navigationLabel = 'Escrow wallet';

    protected static ?string $modelLabel = 'escrow transaction';

    protected static ?string $pluralModelLabel = 'escrow transactions';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLockClosed;

    protected static string|UnitEnum|null $navigationGroup = 'Bookings & Finance';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('booking_id')->label('Booking')->relationship('booking', 'reference')->searchable()->preload()->required(),
            Select::make('type')->label('Type')->options([
                'hold' => 'Hold funds',
                'release' => 'Release to vendor',
                'refund' => 'Refund to client',
                'split' => 'Split',
                'penalty' => 'Penalty',
                'fee' => 'Platform fee',
            ])->required(),
            TextInput::make('amount')->label('Amount')->numeric()->prefix(Finance::currency())->required(),
            Select::make('status')->label('Status')->options([
                'pending' => 'Pending',
                'completed' => 'Completed',
                'failed' => 'Failed',
            ])->default('pending'),
            Textarea::make('notes')->label('Notes'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('booking.reference')->label('Booking')->searchable(),
                TextColumn::make('type')->label('Type')->badge(),
                TextColumn::make('amount')->label('Amount')->money(Finance::currency()),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('created_at')->label('Date')->dateTime('Y-m-d H:i'),
            ])
            ->filters([
                SelectFilter::make('type')->label('Type'),
                SelectFilter::make('status')->label('Status'),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEscrowTransactions::route('/'),
            'create' => Pages\CreateEscrowTransaction::route('/create'),
            'edit' => Pages\EditEscrowTransaction::route('/{record}/edit'),
        ];
    }
}
