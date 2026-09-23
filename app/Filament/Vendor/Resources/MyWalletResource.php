<?php

namespace App\Filament\Vendor\Resources;

use App\Filament\Vendor\Concerns\OwnsVendorRecords;
use App\Filament\Vendor\Resources\MyWalletResource\Pages;
use App\Models\WalletTransaction;
use App\Services\WalletService;
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

class MyWalletResource extends Resource
{
    use OwnsVendorRecords;
    protected static ?string $model = WalletTransaction::class;

    protected static ?string $slug = 'wallet';

    protected static ?string $navigationLabel = 'My wallet';

    protected static ?string $modelLabel = 'transaction';

    protected static ?string $pluralModelLabel = 'wallet ledger';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWallet;

    protected static string|UnitEnum|null $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 0;

    public static function canAccess(): bool
    {
        return Feature::enabled('wallets')
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
        $user = auth()->user();
        $walletId = 0;

        if ($user) {
            $walletId = app(WalletService::class)->ensure($user)->id;
        }

        return parent::getEloquentQuery()
            ->where('wallet_id', $walletId)
            ->with('booking');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label('When')->dateTime('Y-m-d H:i')->sortable(),
                TextColumn::make('type')->label('Type')->badge()->formatStateUsing(fn (string $state): string => str_replace('_', ' ', $state)),
                TextColumn::make('amount')->label('Amount')->money(Finance::currency()),
                TextColumn::make('pending_delta')->label('Pending Δ')->numeric(decimalPlaces: 2),
                TextColumn::make('available_delta')->label('Available Δ')->numeric(decimalPlaces: 2),
                TextColumn::make('booking.reference')->label('Session')->placeholder('—'),
                TextColumn::make('notes')->label('Notes')->limit(48)->wrap(),
            ])
            ->filters([
                SelectFilter::make('type')->options([
                    'earning_hold' => 'Pending earnings',
                    'earning_release' => 'Available earnings',
                    'commission' => 'Commission',
                    'withdrawal' => 'Withdrawn',
                    'hold_reversal' => 'Hold reversal',
                ]),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMyWallet::route('/'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            \App\Filament\Vendor\Widgets\VendorWalletStats::class,
        ];
    }
}
