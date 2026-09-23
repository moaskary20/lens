<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasOptionalFeature;
use App\Filament\Resources\VendorResource\Pages;
use App\Filament\Resources\VendorResource\RelationManagers;
use App\Models\Vendor;
use App\Models\VendorType;
use App\Services\ReputationService;
use App\Support\Finance;
use App\Support\Roles;
use App\Support\VendorProfile;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class VendorResource extends Resource
{
    use HasOptionalFeature;

    protected static ?string $model = Vendor::class;

    protected static ?string $featureKey = 'vendors';

    protected static ?string $navigationLabel = 'Vendors';

    protected static ?string $modelLabel = 'vendor';

    protected static ?string $pluralModelLabel = 'vendors';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCamera;

    protected static string|UnitEnum|null $navigationGroup = 'Marketplace';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'display_name';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user?->isVendor()) {
            return $query->where('user_id', $user->id);
        }

        return $query;
    }

    public static function canView(Model $record): bool
    {
        $user = auth()->user();

        if ($user?->isStaff()) {
            return static::featureEnabled();
        }

        return $user?->ownsVendor($record instanceof Vendor ? $record : null) ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return static::canView($record);
    }

    public static function canCreate(): bool
    {
        return static::featureEnabled() && static::staffAllowed();
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('vendor')->tabs([
                Tab::make('Identity')->schema([
                    Select::make('user_id')->label('User account')->relationship('user', 'name')->searchable()->preload()->required(),
                    Select::make('vendor_type_id')->label('Vendor type')
                        ->relationship(
                            'vendorType',
                            'name_en',
                            fn ($query) => $query->where('is_active', true)->whereIn('slug', Roles::enabledVendorTypeSlugs()),
                        )
                        ->required()
                        ->live()
                        ->native(false)
                        ->helperText('Choosing a type loads the Type profile tab — the same filter-screen fields clients use in the app.'),
                    TextInput::make('display_name')->label('Display name')->required(),
                    ...VendorProfile::personalFields(),
                    ...VendorProfile::contactFields(),
                    Select::make('city_id')->label('Home governorate')->relationship('city', 'name_en')->searchable()->preload(),
                    TextInput::make('address')->label('Address'),
                    TextInput::make('latitude')->label('Latitude')->numeric(),
                    TextInput::make('longitude')->label('Longitude')->numeric(),
                    FileUpload::make('cover_image')->label('Cover image')->image()->directory('vendors/covers')->columnSpanFull(),
                    Select::make('categories')->label('Services / categories')->relationship('categories', 'name_en')->multiple()->preload(),
                    Select::make('filterTags')->label('Filter tags')
                        ->helperText('Optional extra tags. The Type profile tab is the same catalog as the mobile filter screens.')
                        ->relationship(
                            'filterTags',
                            'name_en',
                            function ($query, Get $get) {
                                $slug = VendorType::query()->whereKey($get('vendor_type_id'))->value('slug');
                                if (! $slug) {
                                    return $query;
                                }

                                return $query->where(function ($inner) use ($slug) {
                                    $inner->whereNull('vendor_type_slugs')
                                        ->orWhereJsonLength('vendor_type_slugs', 0)
                                        ->orWhereJsonContains('vendor_type_slugs', $slug);
                                });
                            },
                        )
                        ->multiple()
                        ->preload()
                        ->searchable(),
                    Select::make('badges')->label('Badges')->relationship('badges', 'name_en')->multiple()->preload(),
                ])->columns(2),
                Tab::make('Type profile')->schema(VendorProfile::typeSections())->columns(1),
                Tab::make('Previous projects')->schema([
                    VendorProfile::projectsRepeater()
                        ->helperText('Past jobs the vendor already delivered — photos, reels, UGC clips, or social links.'),
                ]),
                Tab::make('Pricing')->schema(VendorProfile::pricingFields())->columns(2),
                Tab::make('Bank & payouts')->schema(VendorProfile::bankFields())->columns(2),
                Tab::make('Verification & quality')->schema([
                    Select::make('verification_status')->label('Verification')->options([
                        'pending' => 'Pending',
                        'verified' => 'Verified',
                        'rejected' => 'Rejected',
                    ])->native(false),
                    Textarea::make('verification_notes')->label('Verification notes'),
                    Toggle::make('is_active')->label('Visible in marketplace')->default(true),
                    Toggle::make('is_featured')->label('Featured on home'),
                    Toggle::make('accepts_out_of_governorate')->label('Works outside home governorate')
                        ->helperText('If on, a travel fee is added when the booking city is in another governorate.')
                        ->live(),
                    TextInput::make('default_travel_fee')->label('Default transportation fee')->numeric()->prefix(Finance::currency())
                        ->helperText('Used when there is no specific rate for the destination governorate. Set per-governorate rates on the vendor edit page.')
                        ->visible(fn (Get $get): bool => (bool) $get('accepts_out_of_governorate')),
                    TextInput::make('response_minutes')->label('Avg. reply time (minutes)')->numeric(),
                    TextInput::make('booked_sessions')->label('Sessions booked')->numeric()->disabled(),
                    TextInput::make('accepted_sessions')->label('Accepted')->numeric()->disabled(),
                    TextInput::make('rejected_sessions')->label('Rejected')->numeric()->disabled(),
                    TextInput::make('completed_sessions')->label('Completed')->numeric()->disabled(),
                    TextInput::make('failed_sessions')->label('Failed sessions')->numeric()->disabled(),
                    TextInput::make('penalty_total')->label('Applied penalties')->numeric()->prefix(Finance::currency())->disabled(),
                    TextInput::make('rating_avg')->label('Average rating')->numeric()->disabled(),
                    TextInput::make('rating_count')->label('Review count')->numeric()->disabled(),
                ])->columns(2),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('display_name')->label('Name')->searchable()->sortable(),
                TextColumn::make('vendorType.name_en')->label('Type')->badge()->color('primary'),
                TextColumn::make('city.name_en')->label('Governorate'),
                TextColumn::make('verification_status')->label('Verification')->badge()->formatStateUsing(fn (string $state): string => match ($state) {
                    'verified' => 'Verified',
                    'rejected' => 'Rejected',
                    default => 'Pending',
                })->color(fn (string $state): string => match ($state) {
                    'verified' => 'success',
                    'rejected' => 'danger',
                    default => 'warning',
                }),
                TextColumn::make('rating_avg')->label('Rating')->suffix(' ★'),
                TextColumn::make('completed_sessions')->label('Completed'),
                TextColumn::make('failed_sessions')->label('Failed'),
                TextColumn::make('penalty_total')->label('Penalties')->toggleable(),
                IconColumn::make('is_featured')->label('Featured')->boolean(),
                IconColumn::make('is_active')->label('Active')->boolean(),
            ])
            ->filters([
                SelectFilter::make('vendor_type_id')->label('Type')->relationship('vendorType', 'name_en'),
                SelectFilter::make('verification_status')->label('Verification')->options([
                    'pending' => 'Pending',
                    'verified' => 'Verified',
                    'rejected' => 'Rejected',
                ]),
            ])
            ->recordActions([
                Action::make('verify')
                    ->label('Verify')
                    ->icon(Heroicon::OutlinedCheckBadge)
                    ->color('success')
                    ->visible(fn (Vendor $record): bool => $record->verification_status !== 'verified' && Roles::staffCan(auth()->user(), 'verify_vendors'))
                    ->requiresConfirmation()
                    ->action(function (Vendor $record): void {
                        $record->update(['verification_status' => 'verified']);
                        Notification::make()->title('Vendor verified')->success()->send();
                    }),
                Action::make('refreshReputation')
                    ->label('Recalculate reputation')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->action(function (Vendor $record): void {
                        app(ReputationService::class)->refresh($record);
                        Notification::make()->title('Ratings, badges and featured placement recalculated')->success()->send();
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PortfoliosRelationManager::class,
            RelationManagers\AvailabilitiesRelationManager::class,
            RelationManagers\BookingsRelationManager::class,
            RelationManagers\TravelRatesRelationManager::class,
            RelationManagers\ReviewsRelationManager::class,
            RelationManagers\FavoritesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVendors::route('/'),
            'create' => Pages\CreateVendor::route('/create'),
            'edit' => Pages\EditVendor::route('/{record}/edit'),
        ];
    }
}
