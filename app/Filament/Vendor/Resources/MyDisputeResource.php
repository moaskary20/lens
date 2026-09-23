<?php

namespace App\Filament\Vendor\Resources;

use App\Filament\Vendor\Concerns\OwnsVendorRecords;
use App\Filament\Vendor\Resources\MyDisputeResource\Pages;
use App\Models\Dispute;
use App\Support\Feature;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class MyDisputeResource extends Resource
{
    use OwnsVendorRecords;
    protected static ?string $model = Dispute::class;

    protected static ?string $slug = 'disputes';

    protected static ?string $navigationLabel = 'Disputes';

    protected static ?string $modelLabel = 'dispute';

    protected static ?string $pluralModelLabel = 'disputes';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    protected static string|UnitEnum|null $navigationGroup = 'Bookings';

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        return Feature::enabled('disputes')
            && (bool) auth()->user()?->isVendor()
            && auth()->user()?->vendor;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $vendorId = auth()->user()?->vendor?->id ?: 0;

        return parent::getEloquentQuery()
            ->whereHas('booking', fn (Builder $query) => $query->where('vendor_id', $vendorId))
            ->with(['booking', 'opener']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('booking.reference')->label('Session')->searchable(),
                TextColumn::make('kind')->label('Type')->badge(),
                TextColumn::make('opener.name')->label('Opened by'),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('decision')->label('Decision')->placeholder('Waiting on admin'),
                TextColumn::make('reason')->label('Reason')->limit(48),
                TextColumn::make('created_at')->label('Opened')->since(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'open' => 'Open',
                    'reviewing' => 'In review',
                    'resolved' => 'Resolved',
                    'closed' => 'Closed',
                ]),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMyDisputes::route('/'),
        ];
    }
}
