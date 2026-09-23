<?php

namespace App\Filament\Pages\Quality;

use App\Filament\Resources\VendorResource;
use App\Models\Vendor;
use App\Support\Finance;
use App\Support\VendorMetrics;
use Filament\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class VendorMetricsTable extends TableWidget
{
    protected static ?int $sort = 2;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Per-vendor session metrics')
            ->description('Completion and acceptance rates feed Top Rated / Popular badges and featured placement.')
            ->query(Vendor::query()->with(['vendorType', 'badges'])->orderByDesc('completed_sessions'))
            ->columns([
                TextColumn::make('display_name')->label('Vendor')->searchable()->sortable(),
                TextColumn::make('vendorType.name_en')->label('Type')->badge(),
                TextColumn::make('booked_sessions')->label('Booked')->sortable(),
                TextColumn::make('completed_sessions')->label('Completed')->sortable(),
                TextColumn::make('completion_rate')->label('Completion')
                    ->state(fn (Vendor $record): string => VendorMetrics::percent(VendorMetrics::completionRate($record))),
                TextColumn::make('accepted_sessions')->label('Accepted')->sortable(),
                TextColumn::make('rejected_sessions')->label('Rejected')->sortable(),
                TextColumn::make('acceptance_rate')->label('Accepted %')
                    ->state(fn (Vendor $record): string => VendorMetrics::percent(VendorMetrics::acceptanceRate($record))),
                TextColumn::make('failed_sessions')->label('Failed')->sortable()
                    ->color(fn (Vendor $record): string => $record->failed_sessions > 0 ? 'danger' : 'gray'),
                TextColumn::make('penalty_total')->label('Penalties')->money(Finance::currency())->sortable(),
                TextColumn::make('rating_avg')->label('Stars')->suffix(' ★')->sortable(),
                TextColumn::make('badges.name_en')->label('Badges')->badge()->separator(','),
                IconColumn::make('is_featured')->label('Featured')->boolean(),
            ])
            ->recordActions([
                Action::make('open')->label('Open vendor')
                    ->url(fn (Vendor $record): string => VendorResource::getUrl('edit', ['record' => $record])),
            ]);
    }
}
