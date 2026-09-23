<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasOptionalFeature;
use App\Filament\Resources\PricingModelResource\Pages;
use App\Models\PricingModel;
use App\Support\Pricing;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class PricingModelResource extends Resource
{
    use HasOptionalFeature;

    protected static ?string $model = PricingModel::class;

    protected static ?string $staffCapability = 'manage_settings';

    protected static ?string $navigationLabel = 'Pricing models';

    protected static ?string $modelLabel = 'pricing model';

    protected static ?string $pluralModelLabel = 'pricing models';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|UnitEnum|null $navigationGroup = 'Marketplace';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Model')
                ->description('Admin defines the pricing logic. Vendors only fill in their amounts.')
                ->schema([
                    Select::make('vendor_type_id')->label('Vendor type')
                        ->relationship('vendorType', 'name_en', fn ($query) => $query->where('is_active', true)->orderBy('sort_order'))
                        ->required()
                        ->searchable()
                        ->preload()
                        ->helperText('This model is assigned to the selected vendor type. Vendors of that type fill these amounts.'),
                    TextInput::make('name_en')->label('Name')->required(),
                    TextInput::make('name_ar')->label('Arabic name'),
                    TextInput::make('slug')->label('Slug')->required()->unique(ignoreRecord: true),
                    Toggle::make('is_active')->label('Enabled')->default(true),
                    TextInput::make('sort_order')->label('Sort order')->numeric()->default(0),
                    Textarea::make('description')->label('Description')->columnSpanFull(),
                ])->columns(2),
            Section::make('Price fields vendors will fill')
                ->description('Add Half day / Full day, hourly per location, per video, or a new custom package.')
                ->schema([
                    Repeater::make('fields')
                        ->relationship()
                        ->orderColumn('sort_order')
                        ->addActionLabel('Add price field')
                        ->schema([
                            Select::make('key')->label('Field type')->options(Pricing::keyOptions())->required()->live()
                                ->afterStateUpdated(function ($state, Set $set): void {
                                    $template = Pricing::template($state);
                                    if (! $template) {
                                        return;
                                    }
                                    $set('label', $template['label']);
                                    $set('package_type', $template['package_type']);
                                    $set('unit', $template['unit']);
                                    $set('duration_hours', $template['duration_hours']);
                                    $set('storage_key', $template['storage_key']);
                                    $set('helper_text', $template['helper_text']);
                                }),
                            TextInput::make('label')->label('Label shown to vendor')->required(),
                            TextInput::make('package_type')->label('Package key')->required()
                                ->helperText('Used on bookings (half_day, full_day, hourly, per_video, or a new key you invent).'),
                            Select::make('unit')->label('Unit')->options([
                                'session' => 'Fixed package / session',
                                'hour' => 'Per hour (multiplied by booked hours)',
                                'video' => 'Per video',
                                'location' => 'Per location',
                            ])->required(),
                            TextInput::make('duration_hours')->label('Default hours')->numeric(),
                            TextInput::make('storage_key')->label('Custom storage key')
                                ->visible(fn (Get $get): bool => $get('key') === 'custom')
                                ->required(fn (Get $get): bool => $get('key') === 'custom')
                                ->helperText('Saved on the vendor as extras.prices.{key}.'),
                            TextInput::make('helper_text')->label('Helper text')->columnSpanFull(),
                        ])
                        ->columns(2)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('name_en')->label('Model')->searchable(),
                TextColumn::make('vendorType.name_en')->label('Vendor type')->placeholder('—'),
                TextColumn::make('slug')->label('Slug'),
                TextColumn::make('fields_count')->counts('fields')->label('Fields'),
                IconColumn::make('is_active')->label('Enabled')->boolean(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPricingModels::route('/'),
            'create' => Pages\CreatePricingModel::route('/create'),
            'edit' => Pages\EditPricingModel::route('/{record}/edit'),
        ];
    }
}
