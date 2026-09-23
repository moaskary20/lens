<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasOptionalFeature;
use App\Filament\Resources\FavoriteResource\Pages;
use App\Models\Favorite;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class FavoriteResource extends Resource
{
    use HasOptionalFeature;

    protected static ?string $model = Favorite::class;

    protected static ?string $featureKey = 'favorites';

    protected static ?string $slug = 'favorites';

    protected static ?string $navigationLabel = 'Favorites';

    protected static ?string $modelLabel = 'favorite';

    protected static ?string $pluralModelLabel = 'favorites';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHeart;

    protected static string|UnitEnum|null $navigationGroup = 'Users';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Saved creator')->schema([
                Select::make('user_id')->label('Client')->relationship('user', 'email', fn ($query) => $query->where('role', 'client'))
                    ->searchable()->preload()->required(),
                Select::make('vendor_id')->label('Vendor')->relationship('vendor', 'display_name')
                    ->searchable()->preload()->required(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')->label('Client')->searchable(),
                TextColumn::make('user.email')->label('Email')->searchable(),
                TextColumn::make('vendor.display_name')->label('Vendor')->searchable(),
                TextColumn::make('vendor.vendorType.name_en')->label('Type'),
                TextColumn::make('created_at')->label('Saved')->since()->sortable(),
            ])
            ->filters([
                SelectFilter::make('vendor_id')->label('Vendor')->relationship('vendor', 'display_name')->searchable(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFavorites::route('/'),
            'create' => Pages\CreateFavorite::route('/create'),
        ];
    }
}
