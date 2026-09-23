<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasOptionalFeature;
use App\Filament\Resources\RecommendationRuleResource\Pages;
use App\Models\RecommendationRule;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class RecommendationRuleResource extends Resource
{
    use HasOptionalFeature;

    protected static ?string $model = RecommendationRule::class;

    protected static ?string $featureKey = 'recommendation_rules';

    protected static ?string $staffCapability = 'manage_settings';

    protected static ?string $navigationLabel = 'Add-on rules';

    protected static ?string $modelLabel = 'recommendation rule';

    protected static ?string $pluralModelLabel = 'add-on rules';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static string|UnitEnum|null $navigationGroup = 'Search Engine';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('When this happens, suggest an extra service')->schema([
                TextInput::make('name')->label('Rule name')->required(),
                Select::make('trigger_type')->label('Trigger')->required()->options([
                    'category' => 'Project category',
                    'keyword' => 'Keyword in the brief',
                    'vendor_type' => 'Selected vendor type',
                    'filter' => 'Selected filter slug',
                ]),
                TextInput::make('trigger_value')->label('Trigger value')->required()
                    ->helperText('Category slug (fnb), keyword (food shoot), vendor type slug, or filter slug.'),
                Select::make('suggest_vendor_type_id')->label('Suggest vendor type')
                    ->relationship('suggestedVendorType', 'name_en')->preload(),
                Select::make('suggest_filter_tag_ids')->label('Suggest filters')
                    ->multiple()
                    ->options(fn () => \App\Models\FilterTag::query()->pluck('name_en', 'id'))
                    ->searchable(),
                TextInput::make('suggest_message')->label('Client message')
                    ->helperText('Example: Pair this with a food stylist and a studio that has a full kitchen.'),
                Toggle::make('is_active')->label('Enabled')->default(true),
                TextInput::make('sort_order')->label('Sort order')->numeric()->default(0),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Rule')->searchable(),
                TextColumn::make('trigger_type')->label('Trigger')->badge(),
                TextColumn::make('trigger_value')->label('Value'),
                TextColumn::make('suggestedVendorType.name_en')->label('Suggests'),
                IconColumn::make('is_active')->label('On')->boolean(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRecommendationRules::route('/'),
            'create' => Pages\CreateRecommendationRule::route('/create'),
            'edit' => Pages\EditRecommendationRule::route('/{record}/edit'),
        ];
    }
}
