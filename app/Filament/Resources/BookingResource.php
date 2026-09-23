<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasOptionalFeature;
use App\Filament\Resources\BookingResource\Pages;
use App\Filament\Resources\BookingResource\RelationManagers;
use App\Models\Booking;
use App\Models\City;
use App\Models\Coupon;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorAvailability;
use App\Services\PromoService;
use App\Services\ReviewService;
use App\Services\SlotService;
use App\Services\BookingWorkflow;
use App\Services\CancellationService;
use App\Services\DeliveryService;
use App\Services\DisputeService;
use App\Services\EscrowService;
use App\Support\Feature;
use App\Support\Finance;
use App\Support\Pricing;
use App\Support\Roles;
use App\Support\Travel;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use LogicException;
use UnitEnum;

class BookingResource extends Resource
{
    use HasOptionalFeature;

    protected static ?string $model = Booking::class;

    protected static ?string $featureKey = 'bookings';

    protected static ?string $navigationLabel = 'Bookings';

    protected static ?string $modelLabel = 'booking';

    protected static ?string $pluralModelLabel = 'bookings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'Bookings & Finance';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Session')->schema([
                TextInput::make('reference')->label('Reference')->required()->unique(ignoreRecord: true),
                Select::make('client_id')->label('Client')->relationship('client', 'name')->searchable()->preload()->required()
                    ->live()
                    ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculateTravel($get, $set)),
                Select::make('vendor_id')->label('Vendor')->relationship('vendor', 'display_name')->searchable()->preload()->required()
                    ->live()
                    ->afterStateUpdated(function (Get $get, Set $set): void {
                        $set('availability_id', null);
                        $set('package_type', null);
                        self::applyVendorRate($get, $set);
                    }),
                Select::make('category_id')->label('Category')->relationship('category', 'name_en')->preload(),
                Select::make('status')->label('Status')->options(self::statuses())->native(false),
                Select::make('availability_id')->label('Vendor slot')
                    ->options(function (Get $get, ?Booking $record): array {
                        $vendorId = (int) ($get('vendor_id') ?? 0);

                        return $vendorId ? app(SlotService::class)->openSlotOptions($vendorId, $record?->availability_id) : [];
                    })
                    ->searchable()
                    ->helperText('Only free slots are listed. A time that already reached this vendor cannot be booked again.')
                    ->live()
                    ->afterStateUpdated(function ($state, Set $set): void {
                        $slot = $state ? VendorAvailability::query()->find($state) : null;

                        if ($slot) {
                            $set('scheduled_at', $slot->starts_at);
                            $set('duration_hours', max(1, (int) $slot->starts_at->diffInHours($slot->ends_at)));
                        }
                    }),
                DateTimePicker::make('scheduled_at')->label('Scheduled at')
                    ->helperText('Must not overlap another active booking for the same vendor.'),
                TextInput::make('duration_hours')->label('Duration (hours)')->numeric(),
                Select::make('package_type')->label('Package')
                    ->options(function (Get $get): array {
                        $vendor = Vendor::query()->with('vendorType.pricingModel.fields')->find($get('vendor_id'));

                        return $vendor?->resolvedPricingModel()?->packageOptions() ?: [
                            'half_day' => 'Half-day price (6 hours)',
                            'full_day' => 'Full-day price (12 hours)',
                            'hourly' => 'Price per hour per location',
                            'per_video' => 'Price per video',
                        ];
                    })
                    ->live()
                    ->afterStateUpdated(fn (Get $get, Set $set) => self::applyVendorRate($get, $set))
                    ->helperText('Options come from the vendor type pricing model. The session price is filled from the vendor’s own rate.'),
                Select::make('city_id')->label('Shoot governorate')->relationship('city', 'name_en')->searchable()->preload()
                    ->helperText('Compared to the vendor home governorate. A travel fee is added when they differ.')
                    ->live()
                    ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculateTravel($get, $set)),
                TextInput::make('location_text')->label('Location details'),
            ])->columns(2),
            Section::make('Checkout quote')
                ->description('Client pays session + travel (if outside the vendor governorate) + platform fee + tax. Travel is paid in full to the vendor; the 20% commission applies to the session only.')
                ->schema([
                    TextInput::make('session_price')->label('Session price')->numeric()->prefix(Finance::currency())->live(onBlur: true)
                        ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculateTravel($get, $set)),
                    TextInput::make('travel_fee')->label('Transportation fee')->numeric()->prefix(Finance::currency())->readOnly()
                        ->helperText('0 inside the vendor governorate. Otherwise the destination rate, or the vendor default travel fee.'),
                    TextInput::make('client_fee')->label('Client fee (10%)')->numeric()->prefix(Finance::currency())->readOnly(),
                    TextInput::make('tax_amount')->label('Tax')->numeric()->prefix(Finance::currency())->readOnly(),
                    Select::make('coupon_id')->label('Coupon / offer')
                        ->relationship('coupon', 'code')
                        ->getOptionLabelFromRecordUsing(fn ($record): string => $record->code.($record->label ? ' — '.$record->label : ''))
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculateTravel($get, $set))
                        ->helperText('Leave empty to auto-apply the best first-order, loyal, or seasonal offer.'),
                    TextInput::make('discount_amount')->label('Offer discount')->numeric()->prefix(Finance::currency())->readOnly()
                        ->helperText('Taken off the client total. Vendor net is unchanged.'),
                    TextInput::make('total_paid')->label('Client pays (held in escrow)')->numeric()->prefix(Finance::currency())->readOnly(),
                    TextInput::make('vendor_commission')->label('Platform commission (20%)')->numeric()->prefix(Finance::currency())->readOnly(),
                    TextInput::make('vendor_net')->label('Vendor net after completion')->numeric()->prefix(Finance::currency())->readOnly(),
                    Select::make('escrow_status')->label('Escrow status')->options([
                        'none' => 'Disabled',
                        'held' => 'Held (100%)',
                        'released' => 'Released',
                        'refunded' => 'Refunded',
                        'split' => 'Split',
                    ]),
                    Select::make('payout_status')->label('Payout status')->options([
                        'none' => 'None',
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                    ]),
                    TextInput::make('revision_count')->label('Revisions')->numeric(),
                    Textarea::make('notes')->label('Notes')->columnSpanFull(),
                ])->columns(2),
            Section::make('Client project')
                ->description('The client creates this project when they book: a brief plus reference files the vendor works from. Delivery files are uploaded by the vendor on the Delivery tab.')
                ->schema([
                    Textarea::make('client_brief')->label('Project brief')->rows(4)->columnSpanFull()
                        ->helperText('What the client wants delivered — mood, shot list, usage, and deadlines.'),
                    FileUpload::make('client_project_files')
                        ->label('Client project files')
                        ->directory('client-projects')
                        ->multiple()
                        ->maxSize(fn (): int => \App\Support\StorageQuota::clientProjectMaxSizeKb())
                        ->helperText(fn (): string => 'References and assets from the client. Up to '.\App\Support\StorageQuota::clientProjectQuotaMb().' MB per project. Auto-deleted '.\App\Support\StorageQuota::retentionDays().' days after the booking is closed.')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')->label('Reference')->searchable()->copyable(),
                TextColumn::make('client.name')->label('Client')->searchable(),
                TextColumn::make('vendor.display_name')->label('Vendor')->searchable(),
                TextColumn::make('vendor.vendorType.name_en')->label('Type'),
                TextColumn::make('status')->label('Status')->badge()->formatStateUsing(fn (string $state): string => self::statuses()[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'completed', 'approved' => 'success',
                        'cancelled', 'failed', 'rejected' => 'danger',
                        'in_revision', 'disputed' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('total_paid')->label('Held / paid')->money(Finance::currency()),
                TextColumn::make('travel_fee')->label('Travel')->money(Finance::currency())->toggleable(),
                TextColumn::make('escrow_status')->label('Escrow')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'held' => 'warning',
                        'released' => 'success',
                        'refunded', 'split' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('scheduled_at')->label('Schedule')->dateTime('Y-m-d H:i')->sortable(),
                TextColumn::make('client_brief')->label('Project')->limit(28)->toggleable(),
                TextColumn::make('deliverables_count')->counts('deliverables')->label('Delivered files')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Status')->options(self::statuses()),
                SelectFilter::make('escrow_status')->label('Escrow'),
            ])
            ->recordActions([
                self::workflowAction('accept', 'Accept', Heroicon::OutlinedHandThumbUp, 'success',
                    fn (Booking $record): bool => $record->status === 'pending',
                    fn (Booking $record): Booking => app(BookingWorkflow::class)->accept($record),
                    'Vendor accepted. Session counted toward accepted metrics.',
                ),
                self::workflowAction('rejectOffer', 'Reject offer', Heroicon::OutlinedHandThumbDown, 'danger',
                    fn (Booking $record): bool => $record->status === 'pending',
                    fn (Booking $record): Booking => app(BookingWorkflow::class)->reject($record),
                    'Offer rejected. Session counted toward rejected metrics.',
                ),
                self::workflowAction('collect', 'Collect & hold', Heroicon::OutlinedLockClosed, 'warning',
                    fn (Booking $record): bool => $record->escrow_status === 'none' || ($record->escrow_status === 'held' && $record->escrowTransactions()->doesntExist()),
                    fn (Booking $record): Booking => app(EscrowService::class)->checkout($record),
                    '100% of session + client fee + tax is now in escrow.',
                ),
                self::workflowAction('checkIn', 'Check in', Heroicon::OutlinedMapPin, 'info',
                    fn (Booking $record): bool => in_array($record->status, ['pending', 'accepted', 'in_progress'], true) && $record->escrow_status === 'held',
                    fn (Booking $record): Booking => app(EscrowService::class)->checkIn($record),
                    fn (Booking $record): string => app(EscrowService::class)->releasesOnCheckIn($record)
                        ? 'Studio/model check-in released funds to the vendor.'
                        : 'Checked in. Funds stay in escrow until deliverables and client approval.',
                ),
                self::workflowAction('deliver', 'Mark delivered', Heroicon::OutlinedPhoto, 'gray',
                    fn (Booking $record): bool => in_array($record->status, ['checked_in', 'accepted', 'in_progress', 'in_revision'], true),
                    fn (Booking $record): Booking => app(EscrowService::class)->markDelivered($record),
                    'Deliverables stage started. Escrow remains on hold for photographer/videographer types.',
                ),
                Action::make('uploadDelivery')
                    ->label('Upload files')
                    ->icon(Heroicon::OutlinedArrowUpTray)
                    ->visible(fn (Booking $record): bool => Feature::enabled('protected_delivery')
                        && app(DeliveryService::class)->canUpload($record))
                    ->schema([
                        FileUpload::make('path')->label('Retouched / edited file')->directory('deliverables')->required()
                            ->maxSize(fn (): int => \App\Support\StorageQuota::clientProjectMaxSizeKb())
                            ->helperText(fn (): string => 'Up to '.\App\Support\StorageQuota::clientProjectQuotaMb().' MB per client project.'),
                        TextInput::make('original_name')->label('File name'),
                    ])
                    ->action(function (Booking $record, array $data): void {
                        $file = app(DeliveryService::class)->upload(
                            $record,
                            $data['path'],
                            $data['original_name'] ?? null,
                        );
                        Notification::make()
                            ->title('Version '.$file->version.' uploaded. Originals stay locked until Approve.')
                            ->success()
                            ->send();
                    }),
                self::workflowAction(
                    'revise',
                    'Request edit',
                    Heroicon::OutlinedArrowPath,
                    'warning',
                    fn (Booking $record): bool => Feature::enabled('revisions') && in_array($record->status, ['delivered', 'in_revision'], true) && $record->escrow_status === 'held',
                    fn (Booking $record, array $data): Booking => app(DeliveryService::class)->requestRevision($record, $data['note'] ?? null, $record->client_id),
                    'Revision requested. Funds stay on hold. The client note is in chat so the vendor can re-upload.',
                    [
                        Textarea::make('note')->label('Required tweaks')->required()
                            ->helperText('Posted to in-app chat. The vendor then uploads an updated file and the preview loop repeats.'),
                    ],
                ),
                self::workflowAction('approve', 'Approve & release', Heroicon::OutlinedCheckCircle, 'success',
                    fn (Booking $record): bool => in_array($record->status, ['delivered', 'in_revision', 'checked_in'], true) && $record->escrow_status === 'held',
                    fn (Booking $record): Booking => app(DeliveryService::class)->approve($record),
                    'Approved. High-res downloads unlocked. Escrow released: session price minus 20% commission to the vendor wallet.',
                ),
                Action::make('recordReview')
                    ->label('Record star rating')
                    ->icon(Heroicon::OutlinedStar)
                    ->color('warning')
                    ->visible(fn (Booking $record): bool => Feature::enabled('reviews')
                        && in_array($record->status, ['approved', 'completed'], true)
                        && ! $record->review)
                    ->schema([
                        Select::make('rating')->label('Stars')->options(ReviewResource::starOptions())->required()->native(false),
                        Textarea::make('comment')->label('Written review')->rows(3),
                    ])
                    ->action(function (Booking $record, array $data): void {
                        try {
                            app(ReviewService::class)->submit(
                                $record,
                                (int) $data['rating'],
                                $data['comment'] ?? null,
                            );
                            Notification::make()->title('Review saved. Vendor rating and search ranking updated.')->success()->send();
                        } catch (LogicException $exception) {
                            Notification::make()->title($exception->getMessage())->danger()->send();
                        }
                    }),
                self::workflowAction(
                    'openDispute',
                    'Open dispute',
                    Heroicon::OutlinedScale,
                    'danger',
                    fn (Booking $record): bool => Feature::enabled('disputes')
                        && $record->dispute?->isOpen() !== true
                        && ! in_array($record->status, ['cancelled', 'failed', 'refunded', 'approved', 'completed'], true),
                    function (Booking $record, array $data): Booking {
                        $openedBy = (int) ($data['opened_by'] ?? $record->client_id);
                        app(DisputeService::class)->open(
                            $record,
                            $openedBy,
                            $data['reason'] ?? 'Opened from booking',
                            $data['kind'] ?? DisputeService::KIND_DISPUTE,
                        );

                        return $record->fresh();
                    },
                    'Dispute opened. Review the case under Quality & Support → Disputes before moving money.',
                    [
                        Select::make('kind')->label('Type')->options([
                            'dispute' => 'Dispute',
                            'complaint' => 'Complaint',
                        ])->default('dispute')->required(),
                        Select::make('opened_by')->label('Opened by')
                            ->options(fn (Booking $record): array => array_filter([
                                $record->client_id => ($record->client?->name ?? 'Client').' (client)',
                                $record->vendor?->user_id => ($record->vendor?->display_name ?? 'Vendor').' (vendor)',
                            ]))
                            ->default(fn (Booking $record) => $record->client_id)
                            ->required(),
                        Textarea::make('reason')->label('Complaint / reason')->required(),
                    ],
                ),
                self::cancelAction('client'),
                self::cancelAction('vendor'),
                Action::make('overrideRefund')
                    ->label('Override refund')
                    ->icon(Heroicon::OutlinedBanknotes)
                    ->color('danger')
                    ->visible(fn (): bool => Roles::staffCan(auth()->user(), 'refund_overrides'))
                    ->requiresConfirmation()
                    ->action(function (Booking $record): void {
                        $record->update([
                            'status' => 'refunded',
                            'escrow_status' => 'refunded',
                        ]);
                        app(\App\Services\WalletService::class)->onOverrideRefund($record->fresh(['client', 'vendor.user']));
                        Notification::make()->title('Override refund applied to the client wallet')->success()->send();
                    }),
                EditAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\DeliverablesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBookings::route('/'),
            'create' => Pages\CreateBooking::route('/create'),
            'edit' => Pages\EditBooking::route('/{record}/edit'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function statuses(): array
    {
        return [
            'pending' => 'Pending acceptance',
            'accepted' => 'Accepted',
            'rejected' => 'Rejected',
            'checked_in' => 'Checked in',
            'in_progress' => 'In progress',
            'delivered' => 'Delivered',
            'in_revision' => 'In revision',
            'approved' => 'Approved',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'disputed' => 'Disputed',
            'failed' => 'Failed',
            'refunded' => 'Refunded',
        ];
    }

    public static function applyVendorRate(Get $get, Set $set): void
    {
        $vendor = Vendor::query()->with('vendorType.pricingModel.fields')->find($get('vendor_id'));
        $package = $get('package_type');

        if ($vendor && $package) {
            $field = $vendor->pricingFieldFor($package);
            if ($field?->duration_hours) {
                $set('duration_hours', $field->duration_hours);
            }

            $amount = Pricing::sessionAmount($vendor, $package, (float) ($get('duration_hours') ?: $field?->duration_hours));
            if ($amount !== null) {
                $set('session_price', $amount);
            }
        }

        self::recalculateTravel($get, $set);
    }

    public static function applyQuote(Set $set, float $sessionPrice, float $travelFee = 0, float $discount = 0, ?int $couponId = null): void
    {
        $quote = Finance::quote($sessionPrice, $travelFee);
        $discount = min(max(0, $discount), (float) $quote['total_paid']);
        $set('travel_fee', $quote['travel_fee']);
        $set('client_fee', $quote['client_fee']);
        $set('tax_amount', $quote['tax_amount']);
        $set('discount_amount', $discount);
        $set('total_paid', round((float) $quote['total_paid'] - $discount, 2));
        $set('vendor_commission', $quote['vendor_commission']);
        $set('vendor_net', $quote['vendor_net']);
        if ($couponId) {
            $set('coupon_id', $couponId);
        }
    }

    public static function recalculateTravel(Get $get, Set $set): void
    {
        $travel = (float) ($get('travel_fee') ?? 0);
        $vendor = Vendor::query()->with(['city', 'travelRates', 'vendorType'])->find($get('vendor_id'));
        $city = City::query()->find($get('city_id'));

        if ($vendor && $city) {
            try {
                $travel = Travel::fee($vendor, $city);
            } catch (LogicException) {
                $travel = 0;
            }
        }

        $coupon = $get('coupon_id') ? Coupon::query()->with('assignedUsers')->find($get('coupon_id')) : null;
        $client = User::query()->find($get('client_id'));
        $quoted = app(PromoService::class)->quotePrice(
            (float) ($get('session_price') ?? 0),
            $travel,
            $coupon,
            $client,
            $vendor,
        );

        $set('travel_fee', $quoted['travel_fee']);
        $set('client_fee', $quoted['client_fee']);
        $set('tax_amount', $quoted['tax_amount']);
        $set('discount_amount', $quoted['discount_amount']);
        $set('total_paid', $quoted['total_paid']);
        $set('vendor_commission', $quoted['vendor_commission']);
        $set('vendor_net', $quoted['vendor_net']);
        if ($quoted['coupon_id']) {
            $set('coupon_id', $quoted['coupon_id']);
        }
    }

    /**
     * @param  callable(Booking): bool  $visible
     * @param  callable(Booking, array<string, mixed>): Booking  $handler
     * @param  string|callable(Booking): string  $success
     * @param  array<int, mixed>|null  $schema
     */
    protected static function workflowAction(
        string $name,
        string $label,
        Heroicon $icon,
        string $color,
        callable $visible,
        callable $handler,
        string|callable $success,
        ?array $schema = null,
    ): Action {
        $action = Action::make($name)
            ->label($label)
            ->icon($icon)
            ->color($color)
            ->visible($visible)
            ->requiresConfirmation()
            ->action(function (Booking $record, array $data) use ($handler, $success): void {
                try {
                    $updated = $handler($record, $data);
                    $message = is_callable($success) ? $success($updated) : $success;
                    Notification::make()->title($message)->success()->send();
                } catch (LogicException $exception) {
                    Notification::make()->title($exception->getMessage())->danger()->send();
                }
            });

        if ($schema) {
            $action->schema($schema);
        }

        return $action;
    }

    protected static function cancelAction(string $actor): Action
    {
        $isClient = $actor === 'client';

        return Action::make($isClient ? 'cancelClient' : 'cancelVendor')
            ->label($isClient ? 'Client cancel' : 'Vendor cancel / no-show')
            ->icon($isClient ? Heroicon::OutlinedNoSymbol : Heroicon::OutlinedUserMinus)
            ->color($isClient ? 'warning' : 'danger')
            ->visible(fn (Booking $record): bool => Feature::enabled('cancellation_policies') && $record->isCancellable() && Roles::staffCan(auth()->user(), 'policy_exceptions'))
            ->schema([
                Textarea::make('reason')->label('Cancellation reason'),
            ])
            ->modalDescription(function (Booking $record) use ($actor): string {
                try {
                    $quote = app(CancellationService::class)->quote($record, $actor);

                    $currency = Finance::currency();

                    return $quote['policy']->name
                        .". Client refund {$quote['client_refund']} {$currency}"
                        .", vendor net {$quote['vendor_net']} {$currency}"
                        .", platform {$quote['platform_fee']} {$currency}"
                        .($quote['vendor_penalty'] > 0 ? ", vendor penalty {$quote['vendor_penalty']} {$currency}" : '')
                        .'.';
                } catch (LogicException $exception) {
                    return $exception->getMessage();
                }
            })
            ->requiresConfirmation()
            ->action(function (Booking $record, array $data) use ($actor): void {
                try {
                    app(CancellationService::class)->cancel($record, $actor, $data['reason'] ?? null);
                    Notification::make()->title('Cancellation settled using the matching policy tier.')->success()->send();
                } catch (LogicException $exception) {
                    Notification::make()->title($exception->getMessage())->danger()->send();
                }
            });
    }
}
