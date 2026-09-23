<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasOptionalFeature;
use App\Filament\Resources\CmsPageResource\Pages;
use App\Models\CmsPage;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class CmsPageResource extends Resource
{
    use HasOptionalFeature;

    protected static ?string $model = CmsPage::class;

    protected static ?string $featureKey = 'cms';

    protected static ?string $staffCapability = 'manage_settings';

    protected static ?string $navigationLabel = 'CMS pages';

    protected static ?string $modelLabel = 'page';

    protected static ?string $pluralModelLabel = 'CMS pages';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->label('Title')->required(),
            TextInput::make('slug')->label('Slug')->required()->unique(ignoreRecord: true),
            Toggle::make('is_active')->label('Published')->default(true),
            RichEditor::make('body')->label('Content')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('Title')->searchable(),
                TextColumn::make('slug')->label('Slug'),
                IconColumn::make('is_active')->label('Published')->boolean(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCmsPages::route('/'),
            'create' => Pages\CreateCmsPage::route('/create'),
            'edit' => Pages\EditCmsPage::route('/{record}/edit'),
        ];
    }
}
