<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasOptionalFeature;
use App\Filament\Resources\DisputeResource\Pages;
use App\Models\Dispute;
use App\Services\DisputeService;
use App\Support\Finance;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use LogicException;
use UnitEnum;

class DisputeResource extends Resource
{
    use HasOptionalFeature;

    protected static ?string $model = Dispute::class;

    protected static ?string $featureKey = 'disputes';

    protected static ?string $staffCapability = 'resolve_disputes';

    protected static ?string $navigationLabel = 'Disputes';

    protected static ?string $modelLabel = 'dispute';

    protected static ?string $pluralModelLabel = 'disputes & complaints';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    protected static string|UnitEnum|null $navigationGroup = 'Quality & Support';

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::query()->whereIn('status', ['open', 'reviewing'])->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Complaint')->schema([
                Select::make('kind')->label('Type')->options([
                    'dispute' => 'Dispute',
                    'complaint' => 'Complaint',
                ])->default('dispute')->required()->native(false),
                Select::make('booking_id')->label('Booking')->relationship('booking', 'reference')->searchable()->preload()->required(),
                Select::make('opened_by')->label('Opened by')->relationship('opener', 'name')->searchable()->preload()->required(),
                Select::make('status')
                    ->label('Status')
                    ->options(fn (?Dispute $record): array => self::statusOptions($record))
                    ->native(false)
                    ->required()
                    ->default('open')
                    ->disabled(fn (?Dispute $record): bool => filled($record?->id) && ! $record->isOpen())
                    ->helperText('Choose In review while you investigate, or Closed to dismiss without moving money.'),
                Select::make('decision')
                    ->label('Decision')
                    ->options(self::decisions())
                    ->placeholder('Pending')
                    ->native(false)
                    ->nullable()
                    ->disabled(fn (?Dispute $record): bool => filled($record?->id) && ! $record->isOpen())
                    ->helperText('Choose an outcome, then Save. Refund / pay / split moves escrow.'),
                Textarea::make('reason')->label('Complaint / reason')->required()->columnSpanFull(),
            ])->columns(2),
            Section::make('Booking')->schema([
                Placeholder::make('booking_case')->hiddenLabel()->content(fn (?Dispute $record): HtmlString => self::bookingHtml($record))->columnSpanFull(),
            ]),
            Section::make('Payment / escrow')->schema([
                Placeholder::make('payment_case')->hiddenLabel()->content(fn (?Dispute $record): HtmlString => self::paymentHtml($record))->columnSpanFull(),
            ]),
            Section::make('Conversation')->schema([
                Placeholder::make('chat_case')->hiddenLabel()->content(fn (?Dispute $record): HtmlString => self::chatHtml($record))->columnSpanFull(),
            ]),
            Section::make('Decision (split percents of session price)')->schema([
                TextInput::make('client_refund_percent')->label('Client refund %')->numeric()->default(80),
                TextInput::make('vendor_payout_percent')->label('Vendor %')->numeric()->default(10),
                TextInput::make('platform_fee_percent')->label('Platform %')->numeric()->default(10),
                Textarea::make('admin_notes')->label('Admin notes')->columnSpanFull(),
            ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('booking.reference')->label('Booking')->searchable(),
                TextColumn::make('kind')->label('Type')->badge(),
                TextColumn::make('opener.name')->label('Opened by'),
                TextColumn::make('status')->label('Status')->badge()->color(fn (string $state): string => match ($state) {
                    'resolved' => 'success',
                    'closed' => 'gray',
                    'reviewing' => 'info',
                    default => 'warning',
                }),
                TextColumn::make('decision')->label('Decision')->placeholder('Pending')->badge(),
                TextColumn::make('reason')->label('Reason')->limit(40),
                TextColumn::make('created_at')->label('Opened')->since(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Status')->options(self::statuses()),
                SelectFilter::make('kind')->label('Type')->options([
                    'dispute' => 'Dispute',
                    'complaint' => 'Complaint',
                ]),
            ])
            ->recordActions([
                EditAction::make()->label('Review case'),
            ])
            ->defaultSort('id', 'desc');
    }

    /**
     * @return array<Action>
     */
    public static function decisionActions(): array
    {
        $run = function (Dispute $record, string $method, string $success, ?string $notes = null): void {
            try {
                app(DisputeService::class)->{$method}($record, $notes);
                Notification::make()->title($success)->success()->send();
            } catch (LogicException $exception) {
                Notification::make()->title($exception->getMessage())->danger()->send();
            }
        };

        return [
            Action::make('startReview')
                ->label('Start review')
                ->icon(Heroicon::OutlinedEye)
                ->visible(fn (Dispute $record): bool => $record->status === 'open')
                ->action(fn (Dispute $record) => $run($record, 'markReviewing', 'Case marked in review')),
            Action::make('refundClient')
                ->label('Refund client')
                ->icon(Heroicon::OutlinedBanknotes)
                ->color('danger')
                ->visible(fn (Dispute $record): bool => $record->isOpen())
                ->requiresConfirmation()
                ->modalDescription('Returns the session (and travel if held) to the client wallet. Vendor gets nothing.')
                ->action(fn (Dispute $record) => $run($record, 'refundClient', 'Refund sent to the client wallet')),
            Action::make('payVendor')
                ->label('Pay vendor')
                ->icon(Heroicon::OutlinedCheck)
                ->color('success')
                ->visible(fn (Dispute $record): bool => $record->isOpen())
                ->requiresConfirmation()
                ->modalDescription('Releases escrow to the vendor (session minus commission). Files unlock.')
                ->action(fn (Dispute $record) => $run($record, 'payVendor', 'Funds released to the vendor wallet')),
            Action::make('applySplit')
                ->label('Apply split')
                ->icon(Heroicon::OutlinedScale)
                ->color('warning')
                ->visible(fn (Dispute $record): bool => $record->isOpen())
                ->requiresConfirmation()
                ->modalDescription('Uses the percents saved on this case (default 80/10/10). Save the form first if you changed them.')
                ->action(fn (Dispute $record) => $run($record, 'split', 'Split applied and session marked failed')),
            Action::make('closeCase')
                ->label('Close dispute')
                ->icon(Heroicon::OutlinedXMark)
                ->color('gray')
                ->visible(fn (Dispute $record): bool => $record->isOpen())
                ->requiresConfirmation()
                ->modalDescription('Closes without moving money. Escrow stay held so the session can continue.')
                ->schema([
                    Textarea::make('notes')->label('Closing note'),
                ])
                ->action(function (Dispute $record, array $data) use ($run): void {
                    $run($record, 'close', 'Dispute closed without a payout change', $data['notes'] ?? null);
                }),
        ];
    }

    /**
     * Apply Status / Decision chosen on the form. Money movement goes through DisputeService.
     */
    public static function applyFormOutcome(Dispute $dispute, ?string $status, ?string $decision, ?string $notes = null): ?string
    {
        if (! $dispute->isOpen()) {
            return null;
        }

        $service = app(DisputeService::class);

        if (in_array($decision, ['refund', 'payout', 'split', 'closed'], true)) {
            match ($decision) {
                'refund' => $service->refundClient($dispute, $notes),
                'payout' => $service->payVendor($dispute, $notes),
                'split' => $service->split($dispute, $notes),
                'closed' => $service->close($dispute, $notes),
            };

            return match ($decision) {
                'refund' => 'Refund sent to the client wallet',
                'payout' => 'Funds released to the vendor wallet',
                'split' => 'Split applied and session marked failed',
                'closed' => 'Dispute closed without a payout change',
            };
        }

        if ($status === 'reviewing' && $dispute->status === 'open') {
            $service->markReviewing($dispute, $notes);

            return 'Case marked in review';
        }

        if ($status === 'closed') {
            $service->close($dispute, $notes);

            return 'Dispute closed without a payout change';
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    public static function statuses(): array
    {
        return [
            'open' => 'Open',
            'reviewing' => 'In review',
            'resolved' => 'Resolved',
            'closed' => 'Closed',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function statusOptions(?Dispute $record): array
    {
        $options = self::statuses();

        if (! $record?->id || $record->isOpen()) {
            unset($options['resolved']);
        }

        return $options;
    }

    /**
     * @return array<string, string>
     */
    public static function decisions(): array
    {
        return [
            'split' => 'Split (80/10/10 or saved percents)',
            'refund' => 'Refund to client',
            'payout' => 'Pay vendor',
            'closed' => 'Close / dismiss',
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDisputes::route('/'),
            'create' => Pages\CreateDispute::route('/create'),
            'edit' => Pages\EditDispute::route('/{record}/edit'),
        ];
    }

    protected static function bookingHtml(?Dispute $record): HtmlString
    {
        $booking = $record?->booking()?->with(['client', 'vendor', 'city'])->first();
        if (! $booking) {
            return new HtmlString('<p>Save a booking to load the session file.</p>');
        }

        $url = BookingResource::getUrl('edit', ['record' => $booking]);

        return new HtmlString(
            '<p><a class="underline" href="'.e($url).'">Open booking '.$booking->reference.'</a></p>'.
            '<ul class="list-disc ps-5 text-sm space-y-1">'.
            '<li>Client: '.e($booking->client?->name ?? '—').'</li>'.
            '<li>Vendor: '.e($booking->vendor?->display_name ?? '—').'</li>'.
            '<li>Status: '.e($booking->status).' · Escrow: '.e($booking->escrow_status).'</li>'.
            '<li>When: '.e(optional($booking->scheduled_at)->format('Y-m-d H:i') ?: '—').'</li>'.
            '<li>Location: '.e($booking->location_text ?: $booking->city?->name_en ?: '—').'</li>'.
            '</ul>'
        );
    }

    protected static function paymentHtml(?Dispute $record): HtmlString
    {
        $booking = $record?->booking;
        if (! $booking) {
            return new HtmlString('<p>No payment yet.</p>');
        }

        $currency = Finance::currency();
        $rows = $booking->escrowTransactions()->orderBy('id')->get()
            ->map(fn ($tx) => '<li>'.e($tx->type).' · '.number_format((float) $tx->amount, 2).' '.$currency.' — '.e($tx->notes ?: '').'</li>')
            ->implode('');

        $payouts = $booking->payouts()->orderBy('id')->get()
            ->map(fn ($p) => '<li>Payout '.number_format((float) $p->amount, 2).' '.$currency.' · '.e($p->status).'</li>')
            ->implode('');

        return new HtmlString(
            '<ul class="list-disc ps-5 text-sm space-y-1">'.
            '<li>Session '.number_format((float) $booking->session_price, 2).' '.$currency.'</li>'.
            '<li>Client fee '.number_format((float) $booking->client_fee, 2).' · tax '.number_format((float) $booking->tax_amount, 2).'</li>'.
            '<li>Total paid / held '.number_format((float) $booking->total_paid, 2).' '.$currency.'</li>'.
            '<li>Vendor net '.number_format((float) $booking->vendor_net, 2).' · commission '.number_format((float) $booking->vendor_commission, 2).'</li>'.
            '</ul>'.
            ($rows ? '<p class="mt-2 font-medium">Escrow ledger</p><ul class="list-disc ps-5 text-sm space-y-1">'.$rows.'</ul>' : '<p class="mt-2 text-sm">No escrow rows yet.</p>').
            ($payouts ? '<p class="mt-2 font-medium">Payouts</p><ul class="list-disc ps-5 text-sm space-y-1">'.$payouts.'</ul>' : '')
        );
    }

    protected static function chatHtml(?Dispute $record): HtmlString
    {
        $booking = $record?->booking;
        $conversation = $booking?->conversation()?->with('messages.sender')->first();
        if (! $conversation) {
            return new HtmlString('<p>No in-app chat on this booking.</p>');
        }

        $url = ConversationResource::getUrl('edit', ['record' => $conversation]);
        $lines = $conversation->messages->map(function ($message): string {
            return '<p><strong>'.e($message->sender?->name ?? 'Unknown').':</strong> '.nl2br(e($message->body)).'</p>';
        })->implode('');

        return new HtmlString(
            '<p><a class="underline" href="'.e($url).'">Open conversation</a></p>'.
            ($lines !== '' ? $lines : '<p>No messages yet.</p>')
        );
    }
}
