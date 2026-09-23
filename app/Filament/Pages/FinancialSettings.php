<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Models\VendorType;
use App\Support\Feature;
use App\Support\Finance;
use App\Support\Roles;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;
use UnitEnum;

class FinancialSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    protected static ?string $navigationLabel = 'Fees & escrow';

    protected static ?string $title = 'Fees, escrow and commissions';

    protected static string|UnitEnum|null $navigationGroup = 'Bookings & Finance';

    protected static ?int $navigationSort = 0;

    /**
     * @var array<string, mixed>
     */
    public array $data = [];

    public static function canAccess(): bool
    {
        return Roles::staffCan(auth()->user(), 'manage_settings') && Feature::enabled('commissions');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function mount(): void
    {
        $this->form->fill(Finance::settings());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('4.1 Fee structure & commissions')
                    ->description('Client fee is added on top at checkout. Vendor commission is deducted from the session price only when the order successfully completes — never at checkout.')
                    ->schema([
                        Select::make('currency')->label('Currency')->options([
                            'EGP' => 'Egyptian Pound (EGP)',
                            'SAR' => 'Saudi Riyal',
                            'AED' => 'UAE Dirham',
                            'USD' => 'US Dollar',
                        ])->required(),
                        TextInput::make('client_fee_percent')->label('Client platform fee %')->numeric()->required()
                            ->helperText('Added on top of the session price at checkout. Spec: 10%. Example: 1800 session → client pays 1980 before tax.'),
                        TextInput::make('vendor_commission_percent')->label('Vendor platform commission %')->numeric()->required()
                            ->helperText('Deducted from the session price on successful completion. Spec: 20%. Example: 1800 session → vendor net 1440.'),
                        TextInput::make('tax_percent')->label('Tax %')->numeric()->default(0)
                            ->helperText('Applied on (session + client fee). Included in the checkout total held in escrow.'),
                    ])->columns(2),
                Section::make('4.2 Escrow & hold flow')
                    ->description('1) Client pays session + 10% fee + tax. 2) 100% is held in the Lens escrow wallet — nothing goes to the vendor. 3) Payout follows the vendor-type rule below.')
                    ->schema([
                        Toggle::make('hold_full_amount')->label('Hold 100% of collected funds at checkout')
                            ->helperText('Required by spec. Funds stay in Escrow wallet until a release condition fires.'),
                        Toggle::make('keep_hold_during_revisions')->label('Keep funds on hold during revision cycles')
                            ->helperText('Photographers and videographers stay strictly on hold until the client taps Approve.'),
                        Toggle::make('release_requires_deliverables')->label('Require uploaded deliverables before Approve')
                            ->helperText('Photographers and videographers only. Studios and models release on check-in / arrival.'),
                    ]),
                Section::make('4.2 Payout release conditions')
                    ->description('Studios & models: check-in / arrival releases funds (minus 20%). Photographers & videographers: hold through the shoot and post-production; release only after deliverables + Approve. Edit the flag per type under Marketplace → Vendor types.')
                    ->schema([
                        Placeholder::make('payout_matrix')
                            ->label('Live rules')
                            ->content(fn (): HtmlString => new HtmlString(
                                '<ul class="list-disc space-y-1 ps-5 text-sm">'.
                                VendorType::query()->where('is_active', true)->orderBy('sort_order')->get()
                                    ->map(fn (VendorType $type): string => '<li><strong>'.e($type->name_en).'</strong> — '.
                                        ($type->escrow_on_checkin
                                            ? 'Release on check-in / arrival'
                                            : 'Hold until deliverables + client Approve')
                                        .'</li>')
                                    ->implode('').
                                '</ul>'
                            ))
                            ->columnSpanFull(),
                    ]),
                Section::make('Dispute split (section 5.2 C)')
                    ->description('Used only when work is rejected and unresolved.')
                    ->schema([
                        TextInput::make('dispute_client_percent')->label('Client refund %')->numeric(),
                        TextInput::make('dispute_vendor_percent')->label('Vendor time compensation %')->numeric(),
                        TextInput::make('dispute_platform_percent')->label('Lens admin fee %')->numeric(),
                    ])->columns(3),
            ])
            ->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')->label('Save finance settings')->submit('save'),
                    ]),
                ]),
        ]);
    }

    public function save(): void
    {
        Setting::setGroupValues('finance', $this->form->getState());

        Notification::make()->title('Finance settings saved')->success()->send();
    }
}
