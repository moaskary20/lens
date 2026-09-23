<?php

namespace App\Filament\Vendor\Resources;

use App\Filament\Vendor\Concerns\OwnsVendorRecords;
use App\Filament\Vendor\Resources\MyPortfolioResource\Pages;
use App\Models\Portfolio;
use App\Support\Feature;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class MyPortfolioResource extends Resource
{
    use OwnsVendorRecords;
    protected static ?string $model = Portfolio::class;

    protected static ?string $slug = 'portfolio';

    protected static ?string $navigationLabel = 'Previous work';

    protected static ?string $modelLabel = 'project';

    protected static ?string $pluralModelLabel = 'previous work';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Studio';

    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        return Feature::enabled('portfolio')
            && (bool) auth()->user()?->isVendor()
            && auth()->user()?->roleCan('build_portfolio')
            && auth()->user()?->vendor;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('vendor_id', auth()->user()?->vendor?->id ?: 0);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('type')->label('Type')->options([
                'image' => 'Photo',
                'video' => 'Video',
                'link' => 'Social / external link',
            ])->default('image')->live()->required(),
            TextInput::make('title')->label('Project title')->required(),
            FileUpload::make('path')->label('Photo / video file')->directory('portfolios')
                ->visible(fn (Get $get): bool => in_array($get('type'), ['image', 'video'], true))
                ->acceptedFileTypes(fn (Get $get): array => $get('type') === 'video'
                    ? ['video/mp4', 'video/quicktime', 'video/webm']
                    : ['image/jpeg', 'image/png', 'image/webp'])
                ->maxSize(fn (): int => \App\Support\StorageQuota::portfolioMaxSizeKb(auth()->user()?->vendor?->vendorType?->slug))
                ->helperText(fn (): string => 'This vendor category may store up to '.\App\Support\StorageQuota::portfolioQuotaMb(auth()->user()?->vendor?->vendorType?->slug).' MB of portfolio files.'),
            TextInput::make('completed_on')->label('Completed on')->placeholder('2025 or Jun 2025'),
            TextInput::make('external_url')->label('Sample URL')->url()
                ->visible(fn (Get $get): bool => in_array($get('type'), ['link', 'video'], true)),
            Textarea::make('description')->label('What was delivered')->rows(3)->columnSpanFull(),
            Toggle::make('is_featured')->label('Feature this project'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('path')->label('Preview'),
                TextColumn::make('title')->label('Title')->searchable(),
                TextColumn::make('type')->label('Type')->badge(),
                TextColumn::make('completed_on')->label('Completed'),
                IconColumn::make('is_featured')->label('Featured')->boolean(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMyPortfolios::route('/'),
            'create' => Pages\CreateMyPortfolio::route('/create'),
            'edit' => Pages\EditMyPortfolio::route('/{record}/edit'),
        ];
    }
}
