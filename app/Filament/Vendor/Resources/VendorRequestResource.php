<?php

namespace App\Filament\Vendor\Resources;

use App\Filament\Vendor\Concerns\OwnsVendorRecords;
use App\Filament\Vendor\Resources\VendorRequestResource\Pages;
use App\Models\Booking;
use App\Services\BookingWorkflow;
use App\Services\DeliveryService;
use App\Services\DisputeService;
use App\Support\Feature;
use App\Support\Finance;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use LogicException;
use UnitEnum;

class VendorRequestResource extends Resource
{
    use OwnsVendorRecords;
    protected static ?string $model = Booking::class;

    protected static ?string $slug = 'requests';

    protected static ?string $navigationLabel = 'Incoming requests';

    protected static ?string $modelLabel = 'request';

    protected static ?string $pluralModelLabel = 'incoming requests';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInbox;

    protected static string|UnitEnum|null $navigationGroup = 'Bookings';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return Feature::enabled('bookings')
            && (bool) auth()->user()?->isVendor()
            && auth()->user()?->roleCan('receive_bookings')
            && auth()->user()?->vendor;
    }

    public static function getEloquentQuery(): Builder
    {
        $vendorId = auth()->user()?->vendor?->id;

        return parent::getEloquentQuery()
            ->where('vendor_id', $vendorId ?: 0)
            ->with(['client', 'city', 'dispute']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')->label('Reference')->searchable(),
                TextColumn::make('client.name')->label('Client')->searchable(),
                TextColumn::make('package_type')->label('Package')->formatStateUsing(fn (?string $state): string => str_replace('_', ' ', $state ?? '—')),
                TextColumn::make('scheduled_at')->label('When')->dateTime('Y-m-d H:i'),
                TextColumn::make('city.name_en')->label('Governorate'),
                TextColumn::make('session_price')->label('Your rate')->money(Finance::currency()),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('revision_count')->label('Edits')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'pending' => 'Pending',
                    'accepted' => 'Accepted',
                    'rejected' => 'Rejected',
                    'delivered' => 'Delivered',
                    'in_revision' => 'Revision requested',
                    'approved' => 'Approved',
                    'disputed' => 'Disputed',
                    'failed' => 'Failed',
                ]),
            ])
            ->recordActions([
                Action::make('accept')
                    ->label('Accept')
                    ->icon(Heroicon::OutlinedHandThumbUp)
                    ->color('success')
                    ->visible(fn (Booking $record): bool => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(function (Booking $record): void {
                        app(BookingWorkflow::class)->accept($record);
                        Notification::make()->title('Request accepted')->success()->send();
                    }),
                Action::make('reject')
                    ->label('Reject')
                    ->icon(Heroicon::OutlinedHandThumbDown)
                    ->color('danger')
                    ->visible(fn (Booking $record): bool => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(function (Booking $record): void {
                        app(BookingWorkflow::class)->reject($record);
                        Notification::make()->title('Request rejected')->success()->send();
                    }),
                Action::make('uploadDelivery')
                    ->label(fn (Booking $record): string => $record->status === 'in_revision' ? 'Upload revised files' : 'Upload files')
                    ->icon(Heroicon::OutlinedArrowUpTray)
                    ->color('warning')
                    ->visible(fn (Booking $record): bool => Feature::enabled('protected_delivery')
                        && auth()->user()?->roleCan('deliver_assets')
                        && app(DeliveryService::class)->canUpload($record))
                    ->schema([
                        FileUpload::make('path')->label('Edited / high-res file')->directory('deliverables')->required()
                            ->maxSize(fn (): int => \App\Support\StorageQuota::clientProjectMaxSizeKb())
                            ->helperText(fn (): string => 'Up to '.\App\Support\StorageQuota::clientProjectQuotaMb().' MB per client project.'),
                        TextInput::make('original_name')->label('File name'),
                    ])
                    ->modalDescription('Clients only see a protected preview. Originals stay locked until they tap Approve.')
                    ->action(function (Booking $record, array $data): void {
                        try {
                            $file = app(DeliveryService::class)->upload(
                                $record,
                                $data['path'],
                                $data['original_name'] ?? null,
                            );
                            Notification::make()
                                ->title('Version '.$file->version.' sent. Waiting for client approval.')
                                ->success()
                                ->send();
                        } catch (LogicException $exception) {
                            Notification::make()->title($exception->getMessage())->danger()->send();
                        }
                    }),
                Action::make('openDispute')
                    ->label('Open dispute')
                    ->icon(Heroicon::OutlinedScale)
                    ->color('danger')
                    ->visible(fn (Booking $record): bool => Feature::enabled('disputes')
                        && auth()->user()?->roleCan('open_disputes')
                        && $record->dispute?->isOpen() !== true
                        && ! in_array($record->status, ['pending', 'cancelled', 'failed', 'refunded', 'approved', 'completed'], true))
                    ->schema([
                        Select::make('kind')->label('Type')->options([
                            'dispute' => 'Dispute',
                            'complaint' => 'Complaint',
                        ])->default('dispute')->required(),
                        Textarea::make('reason')->label('What happened')->required()->rows(4),
                    ])
                    ->modalDescription('Admin will review the booking, chat, and payment before refunding, paying you, or closing the case.')
                    ->action(function (Booking $record, array $data): void {
                        try {
                            app(DisputeService::class)->open(
                                $record,
                                (int) auth()->id(),
                                $data['reason'],
                                $data['kind'] ?? DisputeService::KIND_DISPUTE,
                            );
                            Notification::make()->title('Dispute sent to Lens admin')->success()->send();
                        } catch (LogicException $exception) {
                            Notification::make()->title($exception->getMessage())->danger()->send();
                        }
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVendorRequests::route('/'),
        ];
    }
}
