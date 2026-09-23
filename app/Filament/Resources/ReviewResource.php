<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasOptionalFeature;
use App\Filament\Resources\ReviewResource\Pages;
use App\Models\Booking;
use App\Models\Review;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class ReviewResource extends Resource
{
    use HasOptionalFeature;

    protected static ?string $model = Review::class;

    protected static ?string $featureKey = 'reviews';

    protected static ?string $navigationLabel = 'Star ratings';

    protected static ?string $modelLabel = 'star rating';

    protected static ?string $pluralModelLabel = 'star ratings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    protected static string|UnitEnum|null $navigationGroup = 'Quality & Support';

    protected static ?int $navigationSort = 2;

    /**
     * @return array<int, string>
     */
    public static function starOptions(): array
    {
        return [
            1 => '1 star',
            2 => '2 stars',
            3 => '3 stars',
            4 => '4 stars',
            5 => '5 stars',
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Client review after approval')
                ->description('1–5 stars plus a written comment. Visible ratings update the vendor average and search ranking immediately.')
                ->schema([
                    Select::make('booking_id')->label('Approved session')
                        ->relationship(
                            'booking',
                            'reference',
                            fn ($query, ?Review $record) => $query
                                ->whereIn('status', ['approved', 'completed'])
                                ->where(function ($inner) use ($record): void {
                                    $inner->whereDoesntHave('review');
                                    if ($record?->booking_id) {
                                        $inner->orWhereKey($record->booking_id);
                                    }
                                }),
                        )
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($state, Set $set): void {
                            $booking = $state ? Booking::query()->find($state) : null;
                            $set('client_id', $booking?->client_id);
                            $set('vendor_id', $booking?->vendor_id);
                        }),
                    Select::make('client_id')->label('Client')->relationship('client', 'name')->searchable()->preload()->required(),
                    Select::make('vendor_id')->label('Vendor')->relationship('vendor', 'display_name')->searchable()->preload()->required(),
                    Select::make('rating')->label('Stars')->options(self::starOptions())->required()->native(false),
                    Toggle::make('is_visible')->label('Visible in marketplace & ranking')->default(true),
                    Textarea::make('comment')->label('Written review')->rows(4)->columnSpanFull(),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('vendor.display_name')->label('Vendor')->searchable()->sortable(),
                TextColumn::make('client.name')->label('Client')->searchable(),
                TextColumn::make('booking.reference')->label('Session')->searchable(),
                TextColumn::make('booking.status')->label('Session status')->badge(),
                TextColumn::make('rating')->label('Stars')
                    ->formatStateUsing(fn (int $state): string => str_repeat('★', $state).str_repeat('☆', 5 - $state))
                    ->sortable(),
                TextColumn::make('comment')->label('Review')->limit(48)->wrap(),
                IconColumn::make('is_visible')->label('Ranks search')->boolean(),
                TextColumn::make('created_at')->label('Submitted')->since()->sortable(),
            ])
            ->filters([
                SelectFilter::make('rating')->label('Stars')->options(self::starOptions()),
                SelectFilter::make('vendor_id')->label('Vendor')->relationship('vendor', 'display_name'),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReviews::route('/'),
            'create' => Pages\CreateReview::route('/create'),
            'edit' => Pages\EditReview::route('/{record}/edit'),
        ];
    }
}
