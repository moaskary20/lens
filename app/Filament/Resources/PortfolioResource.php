<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasOptionalFeature;
use App\Filament\Resources\PortfolioResource\Pages;
use App\Models\Portfolio;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class PortfolioResource extends Resource
{
    use HasOptionalFeature;

    protected static ?string $model = Portfolio::class;

    protected static ?string $featureKey = 'portfolio';

    protected static ?string $navigationLabel = 'Previous work';

    protected static ?string $modelLabel = 'portfolio item';

    protected static ?string $pluralModelLabel = 'previous work';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Marketplace';

    protected static ?int $navigationSort = 6;

    protected static function showsInNavigation(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('vendor_id')->label('Vendor')->relationship('vendor', 'display_name')->searchable()->preload()->required(),
            Select::make('type')->label('Type')->options(['image' => 'Photo', 'video' => 'Video', 'link' => 'Social / external link'])->default('image')->live(),
            FileUpload::make('path')->label('File')->directory('portfolios')
                ->visible(fn (Get $get): bool => in_array($get('type'), ['image', 'video'], true))
                ->maxSize(fn (Get $get): int => \App\Support\StorageQuota::portfolioMaxSizeKb(
                    \App\Models\Vendor::query()->with('vendorType')->find($get('vendor_id'))?->vendorType?->slug
                )),
            TextInput::make('title')->label('Title'),
            Textarea::make('description')->label('What was delivered')->rows(3),
            TextInput::make('completed_on')->label('Completed on')->placeholder('2025 or Jun 2025'),
            TextInput::make('external_url')->label('External URL')->url()
                ->visible(fn (Get $get): bool => in_array($get('type'), ['link', 'video'], true)),
            Toggle::make('is_featured')->label('Featured'),
            TextInput::make('sort_order')->label('Sort order')->numeric()->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('path')->label('Preview'),
                TextColumn::make('vendor.display_name')->label('Vendor')->searchable(),
                TextColumn::make('title')->label('Title'),
                TextColumn::make('type')->label('Type')->badge(),
                IconColumn::make('is_featured')->label('Featured')->boolean(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPortfolios::route('/'),
            'create' => Pages\CreatePortfolio::route('/create'),
            'edit' => Pages\EditPortfolio::route('/{record}/edit'),
        ];
    }
}
