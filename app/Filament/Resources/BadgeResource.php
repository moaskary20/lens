<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasOptionalFeature;
use App\Filament\Resources\BadgeResource\Pages;
use App\Filament\Resources\BadgeResource\RelationManagers;
use App\Models\Badge;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class BadgeResource extends Resource
{
    use HasOptionalFeature;

    protected static ?string $model = Badge::class;

    protected static ?string $featureKey = 'badges';

    protected static ?string $staffCapability = 'manage_settings';

    protected static ?string $navigationLabel = 'Incentive badges';

    protected static ?string $modelLabel = 'badge';

    protected static ?string $pluralModelLabel = 'incentive badges';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCheckBadge;

    protected static string|UnitEnum|null $navigationGroup = 'Quality & Support';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Incentive badge')
                ->description('Top Rated and Popular are awarded automatically from ratings and completion rate, then featured on search.')
                ->schema([
                    TextInput::make('name_en')->label('Name')->required(),
                    TextInput::make('name_ar')->label('Arabic name'),
                    TextInput::make('slug')->label('Slug')->required()->unique(ignoreRecord: true)
                        ->helperText('Use top-rated and popular for automatic awards and featured placement.'),
                    ColorPicker::make('color')->label('Color')->default('#FF5A1F'),
                    TextInput::make('icon')->label('Icon'),
                    Toggle::make('is_automatic')->label('Awarded automatically from metrics')->default(false),
                    Toggle::make('is_active')->label('Enabled')->default(true),
                    Textarea::make('criteria')->label('Award criteria')->columnSpanFull()
                        ->helperText('Shown to staff. Automatic badges also use Reputation engine thresholds.'),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name_en')->label('Badge')->searchable(),
                TextColumn::make('slug')->label('Slug')->badge(),
                ColorColumn::make('color')->label('Color'),
                TextColumn::make('vendors_count')->counts('vendors')->label('Vendors'),
                IconColumn::make('is_automatic')->label('Automatic')->boolean(),
                IconColumn::make('is_active')->label('Enabled')->boolean(),
                TextColumn::make('criteria')->label('Criteria')->limit(40),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\VendorsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBadges::route('/'),
            'create' => Pages\CreateBadge::route('/create'),
            'edit' => Pages\EditBadge::route('/{record}/edit'),
        ];
    }
}
