<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasOptionalFeature;
use App\Filament\Resources\ReplacementOfferResource\Pages;
use App\Models\ReplacementOffer;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class ReplacementOfferResource extends Resource
{
    use HasOptionalFeature;

    protected static ?string $model = ReplacementOffer::class;

    protected static ?string $featureKey = 'replacement_workflow';

    protected static ?string $navigationLabel = 'Replacement offers';

    protected static ?string $modelLabel = 'replacement offer';

    protected static ?string $pluralModelLabel = 'replacement offers';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static string|UnitEnum|null $navigationGroup = 'Bookings & Finance';

    protected static ?int $navigationSort = 7;

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::query()->whereIn('status', ['open', 'offered'])->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('6.2 Replacement marketplace')
                ->description('After a critical vendor cancellation, assign a substitute and send the offer to the client.')
                ->schema([
                    Select::make('booking_id')->label('Cancelled booking')->relationship('booking', 'reference')->searchable()->preload()->required(),
                    Select::make('original_vendor_id')->label('Original vendor')->relationship('originalVendor', 'display_name')->searchable()->preload()->required(),
                    Select::make('suggested_vendor_id')->label('Substitute vendor')->relationship('suggestedVendor', 'display_name')->searchable()->preload(),
                    Select::make('status')->label('Status')->options(self::statuses())->native(false),
                    Textarea::make('notes')->label('Support notes')->columnSpanFull(),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('booking.reference')->label('Booking')->searchable(),
                TextColumn::make('originalVendor.display_name')->label('Original vendor'),
                TextColumn::make('suggestedVendor.display_name')->label('Substitute')->placeholder('Unassigned'),
                TextColumn::make('status')->label('Status')->badge()->formatStateUsing(fn (string $state): string => self::statuses()[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'accepted' => 'success',
                        'declined', 'expired' => 'danger',
                        'offered' => 'info',
                        default => 'warning',
                    }),
                TextColumn::make('created_at')->label('Opened')->since(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Status')->options(self::statuses()),
            ])
            ->recordActions([
                Action::make('offer')
                    ->label('Send offer')
                    ->icon(Heroicon::OutlinedPaperAirplane)
                    ->visible(fn (ReplacementOffer $record): bool => in_array($record->status, ['open', 'offered'], true) && $record->suggested_vendor_id)
                    ->requiresConfirmation()
                    ->action(function (ReplacementOffer $record): void {
                        $record->update(['status' => 'offered']);
                        Notification::make()->title('Substitute offer sent to the client')->success()->send();
                    }),
                Action::make('accept')
                    ->label('Client accepted')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->visible(fn (ReplacementOffer $record): bool => in_array($record->status, ['open', 'offered'], true) && $record->suggested_vendor_id)
                    ->requiresConfirmation()
                    ->action(function (ReplacementOffer $record): void {
                        $record->update(['status' => 'accepted']);
                        Notification::make()->title('Client accepted the substitute vendor')->success()->send();
                    }),
                Action::make('decline')
                    ->label('Decline')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->visible(fn (ReplacementOffer $record): bool => in_array($record->status, ['open', 'offered'], true))
                    ->requiresConfirmation()
                    ->action(function (ReplacementOffer $record): void {
                        $record->update(['status' => 'declined']);
                        Notification::make()->title('Replacement offer declined')->success()->send();
                    }),
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReplacementOffers::route('/'),
            'create' => Pages\CreateReplacementOffer::route('/create'),
            'edit' => Pages\EditReplacementOffer::route('/{record}/edit'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function statuses(): array
    {
        return [
            'open' => 'Open — needs substitute',
            'offered' => 'Offered to client',
            'accepted' => 'Accepted',
            'declined' => 'Declined',
            'expired' => 'Expired',
        ];
    }
}
