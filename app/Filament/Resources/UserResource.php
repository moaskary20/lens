<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Database\Eloquent\Model;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationLabel = 'Users';

    protected static ?string $modelLabel = 'user';

    protected static ?string $pluralModelLabel = 'users';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Users';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Profile')->schema([
                TextInput::make('name')->label('Name')->required()->maxLength(255),
                TextInput::make('email')->label('Email')->email()->required()->unique(ignoreRecord: true),
                TextInput::make('phone')->label('Phone')->tel()->unique(ignoreRecord: true)
                    ->rule(\App\Support\Egypt::mobileRule())
                    ->validationMessages(['regex' => \App\Support\Egypt::mobileMessage()])
                    ->helperText('Egyptian mobile: 11 digits starting with 010, 011, 012, or 015.'),
                TextInput::make('password')->label('Password')->password()->revealable()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state)),
                Select::make('role')->label('Role')->required()
                    ->options(fn (): array => (auth()->user()?->isAdmin() ?? false)
                        ? [
                            'admin' => 'Platform admin',
                            'supervisor' => 'Supervisor',
                            'client' => 'Client (Customer)',
                            'vendor' => 'Vendor (Creator / Studio)',
                        ]
                        : [
                            'client' => 'Client (Customer)',
                            'vendor' => 'Vendor (Creator / Studio)',
                        ])
                    ->disabled(fn (?User $record): bool => (bool) $record && $record->isStaff() && ! (auth()->user()?->isAdmin() ?? false))
                    ->helperText('Clients browse and book. Vendors onboard under a type. Supervisors handle verification and disputes. Admins own settings.')
                    ->native(false),
                Select::make('city_id')->label('Governorate')->relationship('city', 'name_en')->searchable()->preload(),
                Select::make('locale')->label('Language')->options(['en' => 'English', 'ar' => 'Arabic'])->default('en'),
                Toggle::make('is_active')->label('Active account')->default(true),
                FileUpload::make('avatar')->label('Avatar')->image()->directory('avatars')->avatar(),
            ])->columns(2),
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
                TextColumn::make('role')->label('Role')->badge()->formatStateUsing(fn (string $state): string => match ($state) {
                    'admin' => 'Admin',
                    'supervisor' => 'Supervisor',
                    'client' => 'Client',
                    'vendor' => 'Vendor',
                    default => $state,
                })->color(fn (string $state): string => match ($state) {
                    'admin' => 'danger',
                    'supervisor' => 'warning',
                    'vendor' => 'info',
                    default => 'gray',
                }),
                TextColumn::make('city.name_en')->label('Governorate'),
                TextColumn::make('wallet.available')->label('Wallet')->money(\App\Support\Finance::currency())->placeholder('—'),
                IconColumn::make('is_active')->label('Active')->boolean(),
                TextColumn::make('created_at')->label('Joined')->dateTime('Y-m-d')->sortable(),
            ])
            ->filters([
                SelectFilter::make('role')->label('Role')->options([
                    'admin' => 'Admin',
                    'supervisor' => 'Supervisor',
                    'client' => 'Client',
                    'vendor' => 'Vendor',
                ]),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function canViewAny(): bool
    {
        return Roles::staffCan(auth()->user(), 'view_operations');
    }

    public static function canDelete(Model $record): bool
    {
        $actor = auth()->user();

        if ($actor?->isAdmin()) {
            return true;
        }

        return $actor?->staffCan('view_operations') && $record instanceof User && ! $record->isStaff();
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\BookingsRelationManager::class,
            RelationManagers\FavoritesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
