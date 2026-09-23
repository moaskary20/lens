<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasOptionalFeature;
use App\Filament\Resources\CityResource\Pages;
use App\Models\City;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class CityResource extends Resource
{
    use HasOptionalFeature;

    protected static ?string $model = City::class;

    protected static ?string $featureKey = 'cities';

    protected static ?string $staffCapability = 'manage_settings';

    protected static ?string $navigationLabel = 'Governorates';

    protected static ?string $modelLabel = 'governorate';

    protected static ?string $pluralModelLabel = 'governorates';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static string|UnitEnum|null $navigationGroup = 'Marketplace';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name_en')->label('Governorate (English)')->required(),
            TextInput::make('name_ar')->label('المحافظة')->required(),
            TextInput::make('country')->label('Country')->default('EG')->disabled()->dehydrated(),
            TextInput::make('governorate')->label('Travel region key')
                ->helperText('Kept in sync with the English name. Travel fees apply when the shoot governorate differs from the vendor home governorate.'),
            TextInput::make('latitude')->label('Latitude')->numeric(),
            TextInput::make('longitude')->label('Longitude')->numeric(),
            Toggle::make('is_active')->label('Enabled')->default(true),
            TextInput::make('sort_order')->label('Sort order')->numeric()->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name_ar')->label('المحافظة')->searchable(),
                TextColumn::make('name_en')->label('Governorate')->searchable(),
                TextColumn::make('country')->label('Country'),
                IconColumn::make('is_active')->label('Enabled')->boolean(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCities::route('/'),
            'create' => Pages\CreateCity::route('/create'),
            'edit' => Pages\EditCity::route('/{record}/edit'),
        ];
    }
}
