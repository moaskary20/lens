<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasOptionalFeature;
use App\Filament\Resources\PayoutResource\Pages;
use App\Models\Payout;
use App\Support\Finance;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class PayoutResource extends Resource
{
    use HasOptionalFeature;

    protected static ?string $model = Payout::class;

    protected static ?string $featureKey = 'payouts';

    protected static ?string $navigationLabel = 'Payouts';

    protected static ?string $modelLabel = 'payout';

    protected static ?string $pluralModelLabel = 'payouts';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|UnitEnum|null $navigationGroup = 'Bookings & Finance';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('vendor_id')->label('Vendor')->relationship('vendor', 'display_name')->searchable()->preload()->required(),
            Select::make('booking_id')->label('Booking')->relationship('booking', 'reference')->searchable()->preload(),
            TextInput::make('amount')->label('Amount')->numeric()->prefix(Finance::currency())->required(),
            Select::make('status')->label('Status')->options([
                'pending' => 'Pending',
                'processing' => 'Processing',
                'paid' => 'Paid',
                'failed' => 'Failed',
            ])->default('pending'),
            Select::make('method')->label('Method')->options([
                'bank' => 'Bank transfer',
                'wallet' => 'Wallet',
                'stc_pay' => 'STC Pay',
            ]),
            TextInput::make('reference')->label('Transfer reference'),
            DateTimePicker::make('paid_at')->label('Paid at'),
            Textarea::make('notes')->label('Notes'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('vendor.display_name')->label('Vendor')->searchable(),
                TextColumn::make('booking.reference')->label('Booking'),
                TextColumn::make('amount')->label('Amount')->money(Finance::currency()),
                TextColumn::make('status')->label('Status')->badge()->color(fn (string $state): string => match ($state) {
                    'paid' => 'success',
                    'failed' => 'danger',
                    default => 'warning',
                }),
                TextColumn::make('method')->label('Method'),
                TextColumn::make('paid_at')->label('Paid')->dateTime('Y-m-d'),
            ])
            ->filters([
                SelectFilter::make('status')->label('Status'),
            ])
            ->recordActions([
                Action::make('markPaid')
                    ->label('Mark paid')
                    ->icon(Heroicon::OutlinedCheck)
                    ->visible(fn (Payout $record): bool => $record->status !== 'paid')
                    ->requiresConfirmation()
                    ->action(function (Payout $record): void {
                        $record->update(['status' => 'paid', 'paid_at' => now()]);
                        Notification::make()->title('Payout marked as paid')->success()->send();
                    }),
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayouts::route('/'),
            'create' => Pages\CreatePayout::route('/create'),
            'edit' => Pages\EditPayout::route('/{record}/edit'),
        ];
    }
}
