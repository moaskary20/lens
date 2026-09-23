<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasOptionalFeature;
use App\Filament\Resources\WalletResource\Pages;
use App\Filament\Resources\WalletResource\RelationManagers;
use App\Models\Coupon;
use App\Models\Wallet;
use App\Services\WalletService;
use App\Support\Finance;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use LogicException;
use UnitEnum;

class WalletResource extends Resource
{
    use HasOptionalFeature;

    protected static ?string $model = Wallet::class;

    protected static ?string $featureKey = 'wallets';

    protected static ?string $navigationLabel = 'Wallets';

    protected static ?string $modelLabel = 'wallet';

    protected static ?string $pluralModelLabel = 'wallets';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWallet;

    protected static string|UnitEnum|null $navigationGroup = 'Bookings & Finance';

    protected static ?int $navigationSort = 1;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Account')->schema([
                Select::make('user_id')->label('User')->relationship('user', 'name')->disabled(),
                Placeholder::make('role')->label('Role')
                    ->content(fn (?Wallet $record): string => $record?->user?->role ?? '—'),
            ])->columns(2),
            Section::make('Vendor earnings')->schema([
                TextInput::make('lifetime_earned')->label('Total earnings')->numeric()->prefix(Finance::currency())->disabled(),
                TextInput::make('available')->label('Available')->numeric()->prefix(Finance::currency())->disabled(),
                TextInput::make('pending')->label('Pending (in escrow)')->numeric()->prefix(Finance::currency())->disabled(),
                TextInput::make('lifetime_withdrawn')->label('Withdrawn')->numeric()->prefix(Finance::currency())->disabled(),
                TextInput::make('lifetime_commission')->label('Commissions')->numeric()->prefix(Finance::currency())->disabled(),
            ])->columns(2),
            Section::make('Client wallet')->schema([
                TextInput::make('coupon_credit')->label('Coupon credit')->numeric()->prefix(Finance::currency())->disabled(),
                TextInput::make('lifetime_paid')->label('Payments')->numeric()->prefix(Finance::currency())->disabled(),
                TextInput::make('lifetime_refunded')->label('Refunds')->numeric()->prefix(Finance::currency())->disabled(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')->label('User')->searchable()->sortable(),
                TextColumn::make('user.role')->label('Role')->badge(),
                TextColumn::make('available')->label('Available / balance')->money(Finance::currency())->sortable(),
                TextColumn::make('pending')->label('Pending')->money(Finance::currency())->sortable(),
                TextColumn::make('coupon_credit')->label('Coupons')->money(Finance::currency()),
                TextColumn::make('lifetime_earned')->label('Total earned')->money(Finance::currency())->toggleable(),
                TextColumn::make('lifetime_withdrawn')->label('Withdrawn')->money(Finance::currency())->toggleable(),
                TextColumn::make('lifetime_paid')->label('Paid')->money(Finance::currency())->toggleable(),
                TextColumn::make('lifetime_refunded')->label('Refunded')->money(Finance::currency())->toggleable(),
            ])
            ->filters([
                SelectFilter::make('role')->label('Role')->options([
                    'client' => 'Client',
                    'vendor' => 'Vendor',
                    'admin' => 'Admin',
                    'supervisor' => 'Supervisor',
                ])->query(function (Builder $query, array $data): Builder {
                    if (filled($data['value'] ?? null)) {
                        $query->whereHas('user', fn (Builder $inner): Builder => $inner->where('role', $data['value']));
                    }

                    return $query;
                }),
            ])
            ->recordActions([
                Action::make('topUp')
                    ->label('Top up')
                    ->icon(Heroicon::OutlinedPlus)
                    ->schema([
                        TextInput::make('amount')->label('Amount')->numeric()->prefix(Finance::currency())->required()->minValue(0.01),
                        Textarea::make('notes')->label('Notes')->rows(2),
                    ])
                    ->action(function (Wallet $record, array $data): void {
                        app(WalletService::class)->topUp(
                            $record->user,
                            (float) $data['amount'],
                            $data['notes'] ?? 'Manual wallet top-up',
                        );
                        Notification::make()->title('Wallet topped up')->success()->send();
                    }),
                Action::make('applyCoupon')
                    ->label('Apply coupon')
                    ->icon(Heroicon::OutlinedTicket)
                    ->visible(fn (): bool => \App\Support\Feature::enabled('coupons'))
                    ->schema([
                        Select::make('coupon_id')
                            ->label('Coupon')
                            ->options(fn (): array => Coupon::query()->where('is_active', true)->orderBy('code')->pluck('code', 'id')->all())
                            ->required()
                            ->searchable(),
                    ])
                    ->action(function (Wallet $record, array $data): void {
                        try {
                            $coupon = Coupon::query()->findOrFail($data['coupon_id']);
                            app(WalletService::class)->redeemCoupon($record->user, $coupon);
                            Notification::make()->title('Coupon credited to wallet')->success()->send();
                        } catch (LogicException $exception) {
                            Notification::make()->title($exception->getMessage())->danger()->send();
                        }
                    }),
                EditAction::make()->label('Ledger'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\TransactionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWallets::route('/'),
            'edit' => Pages\EditWallet::route('/{record}/edit'),
        ];
    }
}
