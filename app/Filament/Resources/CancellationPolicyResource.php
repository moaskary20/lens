<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasOptionalFeature;
use App\Filament\Resources\CancellationPolicyResource\Pages;
use App\Models\CancellationPolicy;
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

class CancellationPolicyResource extends Resource
{
    use HasOptionalFeature;

    protected static ?string $model = CancellationPolicy::class;

    protected static ?string $featureKey = 'cancellation_policies';

    protected static ?string $staffCapability = 'manage_settings';

    protected static ?string $navigationLabel = 'Cancellation policies';

    protected static ?string $modelLabel = 'cancellation policy';

    protected static ?string $pluralModelLabel = 'cancellation policies';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNoSymbol;

    protected static string|UnitEnum|null $navigationGroup = 'Bookings & Finance';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Applied automatically on cancel')
                ->description('Client and vendor cancel actions pick the first matching active tier by hours of notice. Vendor payout % is the net compensation already stated in the spec (commission already baked in).')
                ->schema([
                TextInput::make('name')->label('Tier name')->required(),
                Select::make('actor')->label('Actor')->options([
                    'client' => 'Client',
                    'vendor' => 'Vendor',
                ])->required(),
                TextInput::make('min_hours')->label('From (hours before session)')->numeric(),
                TextInput::make('max_hours')->label('To (hours before session)')->numeric(),
                TextInput::make('client_refund_percent')->label('Client refund %')->numeric()->suffix('%'),
                TextInput::make('vendor_payout_percent')->label('Vendor payout % (net)')->numeric()->suffix('%')
                    ->helperText('Already the net share. < 24h client cancel uses 80% = session minus the 20% commission.'),
                TextInput::make('platform_fee_percent')->label('Platform fee %')->numeric()->suffix('%'),
                TextInput::make('vendor_penalty_percent')->label('Vendor penalty %')->numeric()->suffix('%')
                    ->helperText('Charged on vendor cancel / no-show (50% same day, 25% with more than 24h notice).'),
                Toggle::make('is_active')->label('Enabled')->default(true),
                TextInput::make('sort_order')->label('Sort order')->numeric()->default(0),
                Textarea::make('description')->label('Description')->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Tier'),
                TextColumn::make('actor')->label('Actor')->formatStateUsing(fn (string $state): string => $state === 'client' ? 'Client' : 'Vendor'),
                TextColumn::make('min_hours')->label('From hours'),
                TextColumn::make('max_hours')->label('To hours'),
                TextColumn::make('client_refund_percent')->label('Client refund')->suffix('%'),
                TextColumn::make('vendor_payout_percent')->label('Vendor payout')->suffix('%'),
                IconColumn::make('is_active')->label('Enabled')->boolean(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCancellationPolicies::route('/'),
            'create' => Pages\CreateCancellationPolicy::route('/create'),
            'edit' => Pages\EditCancellationPolicy::route('/{record}/edit'),
        ];
    }
}
