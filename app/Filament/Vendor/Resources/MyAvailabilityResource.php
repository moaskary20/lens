<?php

namespace App\Filament\Vendor\Resources;

use App\Filament\Vendor\Concerns\OwnsVendorRecords;
use App\Filament\Vendor\Resources\MyAvailabilityResource\Pages;
use App\Models\VendorAvailability;
use App\Support\Feature;
use App\Support\VendorProfile;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class MyAvailabilityResource extends Resource
{
    use OwnsVendorRecords;
    protected static ?string $model = VendorAvailability::class;

    protected static ?string $slug = 'availability';

    protected static ?string $modelLabel = 'slot';

    protected static ?string $pluralModelLabel = 'calendar slots';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'Studio';

    protected static ?int $navigationSort = 0;

    public static function getNavigationLabel(): string
    {
        return VendorProfile::calendarTitle(auth()->user()?->vendor?->vendorType?->slug);
    }

    public static function canAccess(): bool
    {
        return Feature::enabled('bookings')
            && (bool) auth()->user()?->isVendor()
            && auth()->user()?->roleCan('set_availability')
            && auth()->user()?->vendor;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('vendor_id', auth()->user()?->vendor?->id ?: 0);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(static::getNavigationLabel())
                ->description('Open slots can be booked once. Booked slots stay locked until the session ends.')
                ->schema([
                    DateTimePicker::make('starts_at')->label('Starts')->required(),
                    DateTimePicker::make('ends_at')->label('Ends')->required(),
                    Select::make('status')->label('Status')->options([
                        'open' => 'Open for booking',
                        'blocked' => 'Blocked / unavailable',
                        'booked' => 'Booked (locked)',
                    ])->default('open')->required()
                        ->disabled(fn (?VendorAvailability $record): bool => $record?->status === 'booked'),
                    Textarea::make('notes')->label('Notes')->columnSpanFull(),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('starts_at')->label('Starts')->dateTime('Y-m-d H:i')->sortable(),
                TextColumn::make('ends_at')->label('Ends')->dateTime('Y-m-d H:i'),
                TextColumn::make('status')->label('Status')->badge()->formatStateUsing(fn (string $state): string => match ($state) {
                    'open' => 'Open',
                    'booked' => 'Booked',
                    default => 'Blocked',
                })->color(fn (string $state): string => match ($state) {
                    'open' => 'success',
                    'booked' => 'warning',
                    default => 'danger',
                }),
                TextColumn::make('notes')->label('Notes')->limit(40),
            ])
            ->filters([
                SelectFilter::make('status')->label('Status')->options([
                    'open' => 'Open',
                    'booked' => 'Booked',
                    'blocked' => 'Blocked',
                ]),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->visible(fn (VendorAvailability $record): bool => $record->status !== 'booked'),
            ])
            ->defaultSort('starts_at');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMyAvailabilities::route('/'),
            'create' => Pages\CreateMyAvailability::route('/create'),
            'edit' => Pages\EditMyAvailability::route('/{record}/edit'),
        ];
    }
}
