<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasOptionalFeature;
use App\Filament\Resources\AppScreenResource\Pages;
use App\Models\AppScreen;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class AppScreenResource extends Resource
{
    use HasOptionalFeature;

    protected static ?string $model = AppScreen::class;

    protected static ?string $featureKey = 'app_intro';

    protected static ?string $staffCapability = 'manage_settings';

    protected static ?string $navigationLabel = 'Intro screens';

    protected static ?string $modelLabel = 'intro screen';

    protected static ?string $pluralModelLabel = 'intro screens';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDevicePhoneMobile;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->label('Title')->required(),
            TextInput::make('slug')->label('Slug')->required()->unique(ignoreRecord: true),
            Textarea::make('body')->label('Body')->rows(4),
            TextInput::make('cta_label')->label('Button label'),
            FileUpload::make('image')->label('Image')->image()->directory('app-screens'),
            Toggle::make('is_active')->label('Visible')->default(true),
            TextInput::make('sort_order')->label('Sort order')->numeric()->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->columns([
                ImageColumn::make('image')->label('Image'),
                TextColumn::make('title')->label('Title'),
                TextColumn::make('slug')->label('Slug'),
                TextColumn::make('cta_label')->label('Button'),
                IconColumn::make('is_active')->label('Visible')->boolean(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAppScreens::route('/'),
            'create' => Pages\CreateAppScreen::route('/create'),
            'edit' => Pages\EditAppScreen::route('/{record}/edit'),
        ];
    }
}
