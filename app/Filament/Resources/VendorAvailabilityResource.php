<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasOptionalFeature;
use App\Filament\Resources\VendorAvailabilityResource\Pages;
use App\Models\VendorAvailability;
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
use UnitEnum;

class VendorAvailabilityResource extends Resource
{
    use HasOptionalFeature;

    protected static ?string $model = VendorAvailability::class;

    protected static ?string $featureKey = 'bookings';

    protected static ?string $navigationLabel = 'Availability calendar';

    protected static ?string $modelLabel = 'availability slot';

    protected static ?string $pluralModelLabel = 'availability slots';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'Marketplace';

    protected static ?int $navigationSort = 7;

    protected static function showsInNavigation(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Vendor calendar')
                ->description('Open slots can be booked once. After a booking reaches the vendor the slot becomes Booked and disappears from the booking picker.')
                ->schema([
                    Select::make('vendor_id')->label('Vendor')->relationship('vendor', 'display_name')->searchable()->preload()->required(),
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
                TextColumn::make('vendor.display_name')->label('Vendor')->searchable(),
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
                TextColumn::make('notes')->label('Notes')->limit(30),
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
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVendorAvailabilities::route('/'),
            'create' => Pages\CreateVendorAvailability::route('/create'),
            'edit' => Pages\EditVendorAvailability::route('/{record}/edit'),
        ];
    }
}
