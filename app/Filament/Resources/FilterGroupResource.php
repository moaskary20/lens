<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasOptionalFeature;
use App\Filament\Resources\FilterGroupResource\Pages;
use App\Filament\Resources\FilterGroupResource\RelationManagers\OptionsRelationManager;
use App\Models\FilterGroup;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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

class FilterGroupResource extends Resource
{
    use HasOptionalFeature;

    protected static ?string $model = FilterGroup::class;

    protected static ?string $featureKey = 'filters';

    protected static ?string $staffCapability = 'manage_settings';

    protected static ?string $navigationLabel = 'Filter groups';

    protected static ?string $modelLabel = 'filter group';

    protected static ?string $pluralModelLabel = 'filter groups';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquaresPlus;

    protected static string|UnitEnum|null $navigationGroup = 'Search Engine';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Group')->schema([
                TextInput::make('name')->label('Name')->required()
                    ->helperText('Shown as a section in the client search UI.'),
                TextInput::make('slug')->label('Slug')->required()->unique(ignoreRecord: true),
                Textarea::make('description')->label('Admin notes')->rows(2),
                Select::make('scope')->label('Appears when')->required()->options(self::scopes()),
                Select::make('facet_level')->label('Facet level')->required()->options([
                    'primary' => 'Primary facet',
                    'quick' => 'Quick / general filter',
                    'secondary' => 'Secondary / type-specific',
                ]),
                Select::make('input_type')->label('Input type')->required()->options([
                    'checkbox' => 'Checkboxes',
                    'segment' => 'Segmented tabs',
                    'range' => 'Min / max range',
                    'geo' => 'City + distance radius',
                    'date' => 'Date & availability',
                    'budget' => 'Budget slider',
                    'rating' => 'Rating / badge',
                ]),
                Select::make('vendor_type_slugs')->label('Vendor types')->multiple()->options([
                    'photographer' => 'Photographer',
                    'videographer' => 'Videographer',
                    'reels' => 'Mobile Reels Creator',
                    'studio' => 'Studio',
                    'model' => 'Model',
                    'ugc' => 'UGC Creator',
                    'food_stylist' => 'Food Stylist',
                ])->helperText('Leave empty for filters that apply to every search.'),
                Toggle::make('is_active')->label('Visible in the app')->default(true),
                Toggle::make('is_system')->label('System group')->helperText('System groups power location, dates, and budget. Do not delete.'),
                TextInput::make('sort_order')->label('Sort order')->numeric()->default(0),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('name')->label('Group')->searchable(),
                TextColumn::make('scope')->label('Scope')->badge()->formatStateUsing(fn (string $state): string => self::scopes()[$state] ?? $state),
                TextColumn::make('facet_level')->label('Level')->badge(),
                TextColumn::make('input_type')->label('Input'),
                TextColumn::make('options_count')->counts('options')->label('Options'),
                IconColumn::make('is_active')->label('On')->boolean(),
            ])
            ->filters([
                SelectFilter::make('scope')->label('Scope')->options(self::scopes()),
                SelectFilter::make('facet_level')->label('Level')->options([
                    'primary' => 'Primary',
                    'quick' => 'Quick',
                    'secondary' => 'Secondary',
                ]),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getRelations(): array
    {
        return [
            OptionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFilterGroups::route('/'),
            'create' => Pages\CreateFilterGroup::route('/create'),
            'edit' => Pages\EditFilterGroup::route('/{record}/edit'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function scopes(): array
    {
        return [
            'all' => 'All searches',
            'photographer' => 'Photographers',
            'videographer_reels' => 'Videographers & reels',
            'studio' => 'Studios',
            'model' => 'Models',
            'ugc_food' => 'UGC & food stylists',
        ];
    }
}
