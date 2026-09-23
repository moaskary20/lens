<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\BookingResource;
use App\Models\Booking;
use App\Support\Finance;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestBookings extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->isStaff();
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Latest bookings')
            ->query(Booking::query()->latest()->limit(8))
            ->columns([
                TextColumn::make('reference')->label('Reference'),
                TextColumn::make('client.name')->label('Client'),
                TextColumn::make('vendor.display_name')->label('Vendor'),
                TextColumn::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn (string $state): string => BookingResource::statuses()[$state] ?? $state),
                TextColumn::make('total_paid')->label('Amount')->money(Finance::currency()),
                TextColumn::make('scheduled_at')->label('Schedule')->since(),
            ])
            ->recordActions([
                Action::make('open')->label('Open')->url(fn (Booking $record): string => BookingResource::getUrl('edit', ['record' => $record])),
            ]);
    }
}
