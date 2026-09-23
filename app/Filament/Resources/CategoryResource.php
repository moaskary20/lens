<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasOptionalFeature;
use App\Filament\Resources\CategoryResource\Pages;
use App\Models\Category;
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

class CategoryResource extends Resource
{
    use HasOptionalFeature;

    protected static ?string $model = Category::class;

    protected static ?string $featureKey = 'categories';

    protected static ?string $staffCapability = 'manage_settings';

    protected static ?string $navigationLabel = 'Categories';

    protected static ?string $modelLabel = 'category';

    protected static ?string $pluralModelLabel = 'categories';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|UnitEnum|null $navigationGroup = 'Marketplace';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name_en')->label('Name')->required(),
            TextInput::make('name_ar')->label('Arabic name'),
            TextInput::make('slug')->label('Slug')->required()->unique(ignoreRecord: true),
            TextInput::make('icon')->label('Icon'),
            Toggle::make('is_active')->label('Enabled')->default(true),
            TextInput::make('sort_order')->label('Sort order')->numeric()->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('name_en')->label('Category')->searchable(),
                TextColumn::make('slug')->label('Slug'),
                IconColumn::make('is_active')->label('Enabled')->boolean(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}
