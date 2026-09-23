<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasOptionalFeature;
use App\Filament\Resources\CouponResource\Pages;
use App\Models\Coupon;
use App\Models\User;
use App\Services\WalletService;
use App\Support\Finance;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use LogicException;
use UnitEnum;

class CouponResource extends Resource
{
    use HasOptionalFeature;

    protected static ?string $model = Coupon::class;

    protected static ?string $featureKey = 'coupons';

    protected static ?string $navigationLabel = 'Coupons & offers';

    protected static ?string $modelLabel = 'coupon / offer';

    protected static ?string $pluralModelLabel = 'coupons & offers';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    protected static string|UnitEnum|null $navigationGroup = 'Bookings & Finance';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Offer')->schema([
                Select::make('campaign')
                    ->label('Campaign')
                    ->options(Coupon::campaigns())
                    ->default(Coupon::CAMPAIGN_COUPON)
                    ->required()
                    ->native(false)
                    ->live()
                    ->afterStateUpdated(function (mixed $state, Set $set): void {
                        match ($state) {
                            Coupon::CAMPAIGN_SEASONAL => $set('type', Coupon::TYPE_PERCENT),
                            Coupon::CAMPAIGN_VENDOR, Coupon::CAMPAIGN_SERVICE => $set('type', Coupon::TYPE_PERCENT),
                            Coupon::CAMPAIGN_FIRST_ORDER => tap($set('type', Coupon::TYPE_AMOUNT), fn () => $set('auto_apply', true)),
                            Coupon::CAMPAIGN_LOYAL => tap($set('type', Coupon::TYPE_PERCENT), function () use ($set): void {
                                $set('auto_apply', true);
                                $set('min_completed_bookings', 3);
                            }),
                            Coupon::CAMPAIGN_USER => $set('type', Coupon::TYPE_AMOUNT),
                            default => $set('type', Coupon::TYPE_WALLET),
                        };
                    })
                    ->helperText('Discount coupon, seasonal window, specific clients or vendor, a service type, first order, or loyal clients.'),
                TextInput::make('code')->label('Code')->required()->unique(ignoreRecord: true)->maxLength(40)
                    ->helperText('Used on the booking and in the ledger. Auto-apply offers still need a code.'),
                TextInput::make('label')->label('Label')->placeholder('Ramadan studio week'),
                Select::make('type')
                    ->label('Reward')
                    ->options(Coupon::rewardTypes())
                    ->default(Coupon::TYPE_WALLET)
                    ->required()
                    ->native(false)
                    ->live(),
                TextInput::make('amount')
                    ->label(fn (Get $get): string => $get('type') === Coupon::TYPE_PERCENT ? 'Percent' : 'Amount')
                    ->numeric()
                    ->required()
                    ->minValue(0.01)
                    ->maxValue(fn (Get $get): ?float => $get('type') === Coupon::TYPE_PERCENT ? 100 : null)
                    ->prefix(fn (Get $get): ?string => $get('type') === Coupon::TYPE_PERCENT ? null : Finance::currency())
                    ->suffix(fn (Get $get): ?string => $get('type') === Coupon::TYPE_PERCENT ? '%' : null)
                    ->helperText('Wallet credit is added to the client wallet. Percent / amount reduce what the client pays at checkout; vendor net stays the same.'),
                Toggle::make('is_active')->label('Active')->default(true),
                Toggle::make('auto_apply')
                    ->label('Auto-apply at checkout')
                    ->helperText('When on, the best matching offer is applied if the booking has no code. Use for first-order, loyal, or a live seasonal sale.'),
            ])->columns(2),
            Section::make('Targeting')->schema([
                Select::make('assignedUsers')
                    ->label('Specific clients')
                    ->multiple()
                    ->relationship('assignedUsers', 'name', fn ($query) => $query->where('role', 'client'))
                    ->searchable()
                    ->preload()
                    ->visible(fn (Get $get): bool => in_array($get('campaign'), [Coupon::CAMPAIGN_USER, Coupon::CAMPAIGN_COUPON], true))
                    ->required(fn (Get $get): bool => $get('campaign') === Coupon::CAMPAIGN_USER)
                    ->helperText('Leave empty on a generic coupon so any client can use the code.'),
                Select::make('vendor_id')
                    ->label('Vendor')
                    ->relationship('vendor', 'display_name')
                    ->searchable()
                    ->preload()
                    ->visible(fn (Get $get): bool => in_array($get('campaign'), [Coupon::CAMPAIGN_VENDOR, Coupon::CAMPAIGN_COUPON, Coupon::CAMPAIGN_SEASONAL], true))
                    ->required(fn (Get $get): bool => $get('campaign') === Coupon::CAMPAIGN_VENDOR),
                Select::make('vendor_type_id')
                    ->label('Service / vendor type')
                    ->relationship('vendorType', 'name_en')
                    ->searchable()
                    ->preload()
                    ->visible(fn (Get $get): bool => in_array($get('campaign'), [Coupon::CAMPAIGN_SERVICE, Coupon::CAMPAIGN_COUPON, Coupon::CAMPAIGN_SEASONAL], true))
                    ->required(fn (Get $get): bool => $get('campaign') === Coupon::CAMPAIGN_SERVICE),
                TextInput::make('min_completed_bookings')
                    ->label('Min completed sessions')
                    ->numeric()
                    ->default(3)
                    ->minValue(1)
                    ->visible(fn (Get $get): bool => $get('campaign') === Coupon::CAMPAIGN_LOYAL)
                    ->required(fn (Get $get): bool => $get('campaign') === Coupon::CAMPAIGN_LOYAL)
                    ->helperText('Counts approved or completed bookings for that client.'),
            ])->columns(2),
            Section::make('Schedule & limits')->schema([
                DateTimePicker::make('starts_at')->label('Starts')
                    ->visible(fn (Get $get): bool => in_array($get('campaign'), [Coupon::CAMPAIGN_SEASONAL, Coupon::CAMPAIGN_COUPON, Coupon::CAMPAIGN_VENDOR, Coupon::CAMPAIGN_SERVICE], true)),
                DateTimePicker::make('expires_at')->label('Expires'),
                TextInput::make('max_uses')->label('Max uses (all clients)')->numeric()->default(1)->minValue(1),
                TextInput::make('max_uses_per_user')->label('Max uses per client')->numeric()->default(1)->minValue(1),
                TextInput::make('uses_count')->label('Times used')->numeric()->disabled()->dehydrated(false),
                Textarea::make('notes')->label('Notes')->rows(3)->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label('Code')->searchable()->copyable(),
                TextColumn::make('campaign')->label('Campaign')->badge()
                    ->formatStateUsing(fn (?string $state): string => Coupon::campaigns()[$state] ?? ($state ?: 'Coupon')),
                TextColumn::make('label')->label('Label')->limit(28),
                TextColumn::make('type')->label('Reward')->badge()
                    ->formatStateUsing(fn (?string $state): string => Coupon::rewardTypes()[$state] ?? ($state === 'fixed' ? 'Wallet credit' : ($state ?: '—'))),
                TextColumn::make('amount')->label('Value')
                    ->formatStateUsing(function (Coupon $record): string {
                        if ($record->type === Coupon::TYPE_PERCENT) {
                            return rtrim(rtrim(number_format((float) $record->amount, 2), '0'), '.').'%';
                        }

                        return number_format((float) $record->amount, 2).' '.Finance::currency();
                    }),
                TextColumn::make('vendor.display_name')->label('Vendor')->placeholder('—')->toggleable(),
                TextColumn::make('vendorType.name_en')->label('Service')->placeholder('—')->toggleable(),
                TextColumn::make('uses_count')->label('Used')->formatStateUsing(fn (Coupon $record): string => $record->uses_count.'/'.($record->max_uses ?: '∞')),
                IconColumn::make('auto_apply')->label('Auto')->boolean()->toggleable(),
                IconColumn::make('is_active')->label('Active')->boolean(),
                TextColumn::make('expires_at')->label('Expires')->dateTime('Y-m-d')->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('campaign')->label('Campaign')->options(Coupon::campaigns()),
                SelectFilter::make('type')->label('Reward')->options(Coupon::rewardTypes()),
            ])
            ->recordActions([
                Action::make('issue')
                    ->label('Issue to wallet')
                    ->icon(Heroicon::OutlinedWallet)
                    ->visible(fn (Coupon $record): bool => $record->isWalletCredit())
                    ->schema([
                        Select::make('user_id')->label('Client')
                            ->options(fn (): array => User::query()->where('role', 'client')->orderBy('name')->pluck('name', 'id')->all())
                            ->default(fn (Coupon $record) => $record->user_id)
                            ->required()
                            ->searchable(),
                    ])
                    ->action(function (Coupon $record, array $data): void {
                        try {
                            $user = User::query()->findOrFail($data['user_id']);
                            app(WalletService::class)->redeemCoupon($user, $record);
                            Notification::make()->title('Coupon credited to '.$user->name)->success()->send();
                        } catch (LogicException $exception) {
                            Notification::make()->title($exception->getMessage())->danger()->send();
                        }
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCoupons::route('/'),
            'create' => Pages\CreateCoupon::route('/create'),
            'edit' => Pages\EditCoupon::route('/{record}/edit'),
        ];
    }
}
