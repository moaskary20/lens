<?php

namespace App\Filament\Resources\BadgeResource\RelationManagers;

use App\Filament\Resources\VendorResource;
use App\Models\Vendor;
use App\Support\VendorMetrics;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VendorsRelationManager extends RelationManager
{
    protected static string $relationship = 'vendors';

    protected static ?string $title = 'Featured / awarded vendors';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('display_name')
            ->columns([
                TextColumn::make('display_name')->label('Vendor')->searchable(),
                TextColumn::make('rating_avg')->label('Stars')->suffix(' ★'),
                TextColumn::make('rating_count')->label('Reviews'),
                TextColumn::make('completed_sessions')->label('Completed'),
                TextColumn::make('completion')->label('Completion')
                    ->state(fn (Vendor $record): string => VendorMetrics::percent(VendorMetrics::completionRate($record))),
                IconColumn::make('is_featured')->label('Featured placement')->boolean(),
            ])
            ->recordActions([
                Action::make('open')->label('Open')
                    ->url(fn (Vendor $record): string => VendorResource::getUrl('edit', ['record' => $record])),
            ]);
    }
}
