<?php

namespace App\Filament\Vendor\Resources;

use App\Filament\Vendor\Concerns\OwnsVendorRecords;
use App\Filament\Vendor\Resources\MyTravelRateResource\Pages;
use App\Models\VendorTravelRate;
use App\Support\Egypt;
use App\Support\Feature;
use App\Support\Finance;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class MyTravelRateResource extends Resource
{
    use OwnsVendorRecords;
    protected static ?string $model = VendorTravelRate::class;

    protected static ?string $slug = 'travel-fees';

    protected static ?string $navigationLabel = 'Travel fees';

    protected static ?string $modelLabel = 'travel fee';

    protected static ?string $pluralModelLabel = 'travel fees';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static string|UnitEnum|null $navigationGroup = 'Account';

    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        return Feature::enabled('travel_fees')
            && (bool) auth()->user()?->isVendor()
            && auth()->user()?->vendor;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('vendor_id', auth()->user()?->vendor?->id ?: 0);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Travel fees by destination governorate')
                ->description('Used when the shoot is outside your home governorate. The default fee on My profile applies if a destination has no row here.')
                ->schema([
                    Select::make('destination_governorate')
                        ->label('Destination governorate')
                        ->options(Egypt::options())
                        ->searchable()
                        ->required(),
                    TextInput::make('fee')->label('Transportation fee')->numeric()->prefix(Finance::currency())->required(),
                    Textarea::make('notes')->label('Notes')->helperText('Fuel, tickets, or driver cost for this governorate.')->columnSpanFull(),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('destination_governorate')->label('Governorate')->searchable(),
                TextColumn::make('fee')->label('Fee')->money(Finance::currency()),
                TextColumn::make('notes')->label('Notes')->limit(48),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMyTravelRates::route('/'),
            'create' => Pages\CreateMyTravelRate::route('/create'),
            'edit' => Pages\EditMyTravelRate::route('/{record}/edit'),
        ];
    }
}
