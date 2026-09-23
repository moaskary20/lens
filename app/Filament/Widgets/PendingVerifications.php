<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\VendorResource;
use App\Models\Vendor;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class PendingVerifications extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->isStaff();
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Vendors pending verification')
            ->query(Vendor::query()->where('verification_status', 'pending')->latest())
            ->columns([
                TextColumn::make('display_name')->label('Vendor'),
                TextColumn::make('vendorType.name_en')->label('Type'),
                TextColumn::make('city.name_en')->label('Governorate'),
                TextColumn::make('created_at')->label('Submitted')->since(),
            ])
            ->recordActions([
                Action::make('review')->label('Review')->url(fn (Vendor $record): string => VendorResource::getUrl('edit', ['record' => $record])),
            ]);
    }
}
