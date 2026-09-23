<?php

namespace App\Filament\Vendor\Resources;

use App\Filament\Vendor\Concerns\OwnsVendorRecords;
use App\Filament\Vendor\Resources\MyReviewResource\Pages;
use App\Models\Review;
use App\Support\Feature;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class MyReviewResource extends Resource
{
    use OwnsVendorRecords;
    protected static ?string $model = Review::class;

    protected static ?string $slug = 'ratings';

    protected static ?string $navigationLabel = 'Ratings';

    protected static ?string $modelLabel = 'rating';

    protected static ?string $pluralModelLabel = 'ratings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    protected static string|UnitEnum|null $navigationGroup = 'Studio';

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        return Feature::enabled('reviews')
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
        return parent::getEloquentQuery()
            ->where('vendor_id', auth()->user()?->vendor?->id ?: 0)
            ->with(['client', 'booking']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('client.name')->label('Client')->searchable(),
                TextColumn::make('booking.reference')->label('Session'),
                TextColumn::make('rating')->label('Stars')
                    ->formatStateUsing(fn (int $state): string => str_repeat('★', $state).str_repeat('☆', 5 - $state)),
                TextColumn::make('comment')->label('Review')->limit(60)->wrap(),
                IconColumn::make('is_visible')->label('Public')->boolean(),
                TextColumn::make('created_at')->label('Received')->since(),
            ])
            ->filters([
                SelectFilter::make('rating')->options([
                    1 => '1 star',
                    2 => '2 stars',
                    3 => '3 stars',
                    4 => '4 stars',
                    5 => '5 stars',
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMyReviews::route('/'),
        ];
    }
}
