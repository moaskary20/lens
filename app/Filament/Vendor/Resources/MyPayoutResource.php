<?php

namespace App\Filament\Vendor\Resources;

use App\Filament\Vendor\Concerns\OwnsVendorRecords;
use App\Filament\Vendor\Resources\MyPayoutResource\Pages;
use App\Models\Payout;
use App\Support\Feature;
use App\Support\Finance;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class MyPayoutResource extends Resource
{
    use OwnsVendorRecords;
    protected static ?string $model = Payout::class;

    protected static ?string $slug = 'earnings';

    protected static ?string $navigationLabel = 'Payments & earnings';

    protected static ?string $modelLabel = 'payout';

    protected static ?string $pluralModelLabel = 'payments';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWallet;

    protected static string|UnitEnum|null $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return Feature::enabled('payouts')
            && (bool) auth()->user()?->isVendor()
            && auth()->user()?->vendor;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('vendor_id', auth()->user()?->vendor?->id ?: 0)
            ->with('booking');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('booking.reference')->label('Session')->placeholder('—'),
                TextColumn::make('amount')->label('Amount')->money(Finance::currency()),
                TextColumn::make('status')->label('Status')->badge()->color(fn (string $state): string => match ($state) {
                    'paid' => 'success',
                    'failed' => 'danger',
                    default => 'warning',
                }),
                TextColumn::make('method')->label('Method')->placeholder('—'),
                TextColumn::make('paid_at')->label('Paid')->dateTime('Y-m-d'),
                TextColumn::make('notes')->label('Notes')->limit(40)->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'pending' => 'Pending',
                    'processing' => 'Processing',
                    'paid' => 'Paid',
                    'failed' => 'Failed',
                ]),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMyPayouts::route('/'),
        ];
    }
}
