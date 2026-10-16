<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClientResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use App\Support\ClientPreferences;
use App\Support\Egypt;
use App\Support\Roles;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class ClientResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $slug = 'clients';

    protected static ?string $navigationLabel = 'Client (Customer)';

    protected static ?string $modelLabel = 'client';

    protected static ?string $pluralModelLabel = 'clients';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Marketplace';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('role', Roles::CLIENT);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Profile')->schema([
                TextInput::make('name')->label('Name')->required()->maxLength(255),
                TextInput::make('email')->label('Email')->email()->required()->unique(ignoreRecord: true),
                TextInput::make('phone')->label('Phone')->tel()->unique(ignoreRecord: true)
                    ->rule(Egypt::mobileRule())
                    ->validationMessages(['regex' => Egypt::mobileMessage()])
                    ->helperText('Egyptian mobile: 11 digits starting with 010, 011, 012, or 015.'),
                TextInput::make('password')->label('Password')->password()->revealable()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state)),
                Select::make('city_id')->label('Governorate')->relationship('city', 'name_en')->searchable()->preload(),
                Select::make('locale')->label('Language')->options(ClientPreferences::languages())->default('en')->live()
                    ->afterStateUpdated(fn (Set $set, ?string $state) => $set('app_settings.language', $state)),
                Toggle::make('is_active')->label('Active account')->default(true),
                FileUpload::make('avatar')->label('Avatar')->image()->directory('avatars')->avatar(),
            ])->columns(2),
            Section::make('App settings')
                ->description('Client preferences applied in the mobile app after the next refresh.')
                ->schema([
                    Section::make('Alerts')->schema([
                        UserResource::preferenceSelect('push_notifications'),
                        UserResource::preferenceSelect('chat_alerts'),
                        UserResource::preferenceSelect('message_preview'),
                        UserResource::preferenceSelect('booking_reminders'),
                        UserResource::preferenceSelect('review_prompts'),
                        UserResource::preferenceSelect('email_offers'),
                        UserResource::preferenceSelect('vibration'),
                    ])->columns(2),
                    Section::make('Privacy')->schema([
                        UserResource::preferenceSelect('read_receipts'),
                        UserResource::preferenceSelect('hide_activity'),
                    ])->columns(2),
                    Section::make('Display & feel')->schema([
                        UserResource::preferenceSelect('haptic_feedback'),
                        UserResource::preferenceSelect('reduce_motion'),
                        UserResource::preferenceSelect('language'),
                    ])->columns(2),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('avatar')->label('')->circular(),
                TextColumn::make('name')->label('Name')->searchable()->sortable(),
                TextColumn::make('email')->label('Email')->searchable(),
                TextColumn::make('phone')->label('Phone')->searchable(),
                TextColumn::make('city.name_en')->label('Governorate'),
                IconColumn::make('is_active')->label('Active')->boolean(),
                TextColumn::make('created_at')->label('Joined')->dateTime('Y-m-d')->sortable(),
            ])
            ->recordActions([
                EditAction::make()->visible(fn (): bool => static::canManageAccounts()),
                DeleteAction::make()->visible(fn (): bool => static::canManageAccounts()),
            ])
            ->toolbarActions([
                DeleteBulkAction::make()->visible(fn (): bool => static::canManageAccounts()),
            ]);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function canViewAny(): bool
    {
        return Roles::staffCan(auth()->user(), 'view_operations')
            || Roles::staffCan(auth()->user(), 'view_users');
    }

    public static function canCreate(): bool
    {
        return static::canManageAccounts();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canManageAccounts() && $record instanceof User && $record->isClient();
    }

    public static function canDelete(Model $record): bool
    {
        $actor = auth()->user();

        return $record instanceof User
            && $record->isClient()
            && (($actor?->isAdmin() ?? false) || ($actor?->staffCan('view_operations') ?? false));
    }

    public static function canManageAccounts(): bool
    {
        return Roles::staffCan(auth()->user(), 'view_operations');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\BookingsRelationManager::class,
            RelationManagers\FavoritesRelationManager::class,
            RelationManagers\PaymentMethodsRelationManager::class,
            RelationManagers\AddressesRelationManager::class,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function normalizeClientData(array $data): array
    {
        $data['role'] = Roles::CLIENT;

        return UserResource::normalizePreferenceData($data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function hydrateClientData(array $data): array
    {
        return UserResource::hydratePreferences($data);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClients::route('/'),
            'create' => Pages\CreateClient::route('/create'),
            'edit' => Pages\EditClient::route('/{record}/edit'),
        ];
    }
}
