<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasOptionalFeature;
use App\Filament\Resources\VendorTypeResource\Pages;
use App\Models\VendorType;
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
use Filament\Tables\Table;
use UnitEnum;

class VendorTypeResource extends Resource
{
    use HasOptionalFeature;

    protected static ?string $model = VendorType::class;

    protected static ?string $staffCapability = 'manage_settings';

    protected static ?string $navigationLabel = 'Vendor types';

    protected static ?string $modelLabel = 'vendor type';

    protected static ?string $pluralModelLabel = 'vendor types';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|UnitEnum|null $navigationGroup = 'Marketplace';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->schema([
                TextInput::make('name_en')->label('Name')->required(),
                TextInput::make('name_ar')->label('Arabic name'),
                TextInput::make('slug')->label('Slug')->required()->unique(ignoreRecord: true),
                Select::make('pricing_model_id')->label('Pricing model')
                    ->relationship('pricingModel', 'name_en', fn ($query) => $query->where('is_active', true)->orderBy('sort_order'))
                    ->required()
                    ->searchable()
                    ->preload()
                    ->helperText('Admin creates and edits models under Marketplace → Pricing models. The vendor only fills the amounts.'),
                Toggle::make('escrow_on_checkin')->label('Release on check-in / arrival (studios & models)')
                    ->helperText('On: funds release when the booking is checked in at the location (20% commission deducted then). Off: photographers and videographers — 100% stays in escrow until deliverables are uploaded and the client taps Approve. Revisions stay on hold.'),
                Toggle::make('is_active')->label('Enabled')->default(true),
                TextInput::make('sort_order')->label('Sort order')->numeric()->default(0),
                Textarea::make('description')->label('Description')->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('name_en')->label('Type')->searchable(),
                TextColumn::make('pricingModel.name_en')->label('Pricing'),
                TextColumn::make('escrow_on_checkin')->label('Payout')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Check-in / arrival' : 'Deliverables + Approve')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'success' : 'warning'),
                IconColumn::make('is_active')->label('Enabled')->boolean(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVendorTypes::route('/'),
            'create' => Pages\CreateVendorType::route('/create'),
            'edit' => Pages\EditVendorType::route('/{record}/edit'),
        ];
    }
}
