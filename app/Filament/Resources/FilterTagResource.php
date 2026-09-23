<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasOptionalFeature;
use App\Filament\Resources\FilterTagResource\Pages;
use App\Models\FilterTag;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class FilterTagResource extends Resource
{
    use HasOptionalFeature;

    protected static ?string $model = FilterTag::class;

    protected static ?string $featureKey = 'filters';

    protected static ?string $staffCapability = 'manage_settings';

    protected static ?string $navigationLabel = 'Filter options';

    protected static ?string $modelLabel = 'filter option';

    protected static ?string $pluralModelLabel = 'filter options';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFunnel;

    protected static string|UnitEnum|null $navigationGroup = 'Search Engine';

    protected static ?int $navigationSort = 3;

    protected static function showsInNavigation(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Option')->schema([
                Select::make('filter_group_id')->label('Group')->relationship('group', 'name')->searchable()->preload()->required(),
                TextInput::make('name_en')->label('Label')->required(),
                TextInput::make('name_ar')->label('Arabic label'),
                TextInput::make('slug')->label('Slug')->required()->unique(ignoreRecord: true),
                TextInput::make('helper_text')->label('Helper text')->columnSpanFull(),
                TagsInput::make('synonyms')->label('AI synonyms')
                    ->helperText('Natural-language words that should match this option, e.g. kitchen, full kitchen, cooking set.'),
                Select::make('vendor_type_slugs')->label('Applies to types')->multiple()->options([
                    'photographer' => 'Photographer',
                    'videographer' => 'Videographer',
                    'reels' => 'Mobile Reels Creator',
                    'studio' => 'Studio',
                    'model' => 'Model',
                    'ugc' => 'UGC Creator',
                    'food_stylist' => 'Food Stylist',
                ]),
                TextInput::make('unit')->label('Unit')->placeholder('cm, km, EGP'),
                TextInput::make('min_value')->label('Min value')->numeric(),
                TextInput::make('max_value')->label('Max value')->numeric(),
                Toggle::make('is_active')->label('Enabled')->default(true),
                Toggle::make('show_in_quick_filters')->label('Show in quick filters'),
                TextInput::make('sort_order')->label('Sort order')->numeric()->default(0),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name_en')->label('Option')->searchable(),
                TextColumn::make('group.name')->label('Group')->badge(),
                TextColumn::make('synonyms')->label('AI synonyms')->limit(30),
                IconColumn::make('is_active')->label('On')->boolean(),
            ])
            ->filters([
                SelectFilter::make('filter_group_id')->label('Group')->relationship('group', 'name'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFilterTags::route('/'),
            'create' => Pages\CreateFilterTag::route('/create'),
            'edit' => Pages\EditFilterTag::route('/{record}/edit'),
        ];
    }
}
