<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Models\VendorType;
use App\Support\BrevoMail;
use App\Support\Roles;
use App\Support\StorageQuota;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class PlatformSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Platform settings';

    protected static ?string $title = 'General platform settings';

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 3;

    /**
     * @var array<string, mixed>
     */
    public array $data = [];

    public static function canAccess(): bool
    {
        return Roles::staffCan(auth()->user(), 'manage_settings');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function mount(): void
    {
        $this->form->fill(array_merge([
            'app_name' => 'Lens',
            'support_email' => 'support@lens.app',
            'support_phone' => '',
            'default_locale' => 'en',
            'maintenance_mode' => false,
        ], Setting::groupValues('platform'), StorageQuota::settings(), BrevoMail::formState()));
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('testEmail')
                ->label('Send Brevo test email')
                ->icon(Heroicon::OutlinedPaperAirplane)
                ->form([
                    TextInput::make('to')
                        ->label('Send to')
                        ->email()
                        ->required()
                        ->default(fn (): ?string => auth()->user()?->email),
                ])
                ->action(function (array $data): void {
                    try {
                        BrevoMail::sendTest((string) $data['to']);
                        Notification::make()->title('Test email sent via Brevo')->success()->send();
                    } catch (\Throwable $exception) {
                        Notification::make()->title($exception->getMessage())->danger()->send();
                    }
                }),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Brand')->schema([
                    TextInput::make('app_name')->label('App name')->required(),
                    FileUpload::make('logo')->label('Logo')->image()->directory('branding'),
                    TextInput::make('support_email')->label('Support email')->email(),
                    TextInput::make('support_phone')->label('Support phone'),
                    Toggle::make('maintenance_mode')->label('Maintenance mode'),
                    Textarea::make('about')->label('About the platform')->columnSpanFull(),
                ])->columns(2),
                Section::make('File storage')
                    ->description('Limit vendor portfolio uploads by category, cap client project files, and auto-delete closed client projects.')
                    ->schema([
                        TextInput::make('default_portfolio_quota_mb')
                            ->label('Default portfolio quota')
                            ->numeric()
                            ->minValue(1)
                            ->suffix('MB')
                            ->required()
                            ->helperText('Used for any vendor category without its own value. Default: 500 MB.'),
                        ...$this->portfolioQuotaInputs(),
                        TextInput::make('client_project_quota_mb')
                            ->label('Client project files')
                            ->numeric()
                            ->minValue(1)
                            ->suffix('MB')
                            ->required()
                            ->helperText('Per client project / booking, including reference files and delivered work. Default: 2048 MB (2 GB).'),
                        TextInput::make('client_project_retention_days')
                            ->label('Auto-delete client project files after')
                            ->numeric()
                            ->minValue(1)
                            ->suffix('days')
                            ->required()
                            ->helperText('Files are removed automatically this many days after a booking is completed, cancelled, rejected, or refunded. Default: 7 days.'),
                    ])->columns(2),
                ...$this->brevoSections(),
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
                        Action::make('save')->label('Save platform settings')->submit('save'),
                    ]),
                ]),
        ]);
    }

    public function save(): void
    {
        [$platform, $mail] = BrevoMail::splitState($this->form->getState());
        Setting::setGroupValues('platform', $platform);
        Setting::setGroupValues('mail', BrevoMail::sanitize($mail));
        BrevoMail::apply();

        Notification::make()->title('Platform settings saved')->success()->send();
    }

    /**
     * @return list<Section>
     */
    protected function brevoSections(): array
    {
        $whenOn = fn (Get $get): bool => (bool) $get('brevo_enabled');

        return [
            Section::make('Brevo email')
                ->description('Transactional email for Lens. Create the account at brevo.com, then copy keys from Settings → SMTP & API. The sender address must be verified under Senders, IPs & Domains.')
                ->schema([
                    Toggle::make('brevo_enabled')->label('Enable Brevo')->live()
                        ->helperText('When off, Laravel keeps using MAIL_* from .env (usually the log driver).'),
                    Select::make('brevo_transport')->label('Send through')
                        ->options([
                            'smtp' => 'SMTP relay (smtp-relay.brevo.com)',
                            'api' => 'Brevo Transactional API',
                        ])
                        ->required()
                        ->visible($whenOn)
                        ->helperText('SMTP uses Laravel Mail. API posts to /v3/smtp/email and can use Brevo templates.'),
                    TextInput::make('brevo_api_key')->label('API key')->password()->revealable()
                        ->visible($whenOn)
                        ->dehydrated(fn ($state): bool => filled($state))
                        ->helperText(fn (): string => BrevoMail::hasApiKey()
                            ? 'A key is already saved. Leave blank to keep it. From SMTP & API → API Keys (xkeysib-…).'
                            : 'From Brevo → Settings → SMTP & API → API Keys. Starts with xkeysib-.'),
                    TextInput::make('brevo_api_base_url')->label('API base URL')
                        ->visible($whenOn)
                        ->helperText('Default https://api.brevo.com/v3'),
                    TextInput::make('brevo_webhook_secret')->label('Webhook secret')->password()->revealable()
                        ->visible($whenOn)
                        ->dehydrated(fn ($state): bool => filled($state))
                        ->helperText(fn (): string => BrevoMail::hasWebhookSecret()
                            ? 'A secret is already saved. Leave blank to keep it.'
                            : 'Optional. Used to verify bounce / delivered webhooks from Transactional → Settings → Webhooks.'),
                ])->columns(2),
            Section::make('Brevo sender')
                ->description('Must match a verified sender in Brevo. Clients see this name on booking and account emails.')
                ->visible($whenOn)
                ->schema([
                    TextInput::make('brevo_from_email')->label('From email')->email()->required(fn (Get $get): bool => (bool) $get('brevo_enabled')),
                    TextInput::make('brevo_from_name')->label('From name'),
                    TextInput::make('brevo_reply_to_email')->label('Reply-to email')->email(),
                    TextInput::make('brevo_reply_to_name')->label('Reply-to name'),
                    TextInput::make('brevo_bcc_email')->label('BCC staff copy')->email()
                        ->helperText('Optional. A copy of every transactional email.'),
                    Toggle::make('brevo_track_opens')->label('Track opens'),
                    Toggle::make('brevo_track_clicks')->label('Track clicks'),
                ])->columns(2),
            Section::make('Brevo SMTP')
                ->description('From Settings → SMTP & API → SMTP. Login is usually the Brevo account email. The SMTP key is different from the API key.')
                ->visible(fn (Get $get): bool => (bool) $get('brevo_enabled') && $get('brevo_transport') === 'smtp')
                ->schema([
                    TextInput::make('brevo_smtp_host')->label('SMTP host')->required(fn (Get $get): bool => (bool) $get('brevo_enabled') && $get('brevo_transport') === 'smtp'),
                    TextInput::make('brevo_smtp_port')->label('Port')->numeric()->minValue(1),
                    Select::make('brevo_smtp_encryption')->label('Encryption')->options([
                        'tls' => 'TLS (port 587)',
                        'ssl' => 'SSL (port 465)',
                        'none' => 'None',
                    ]),
                    TextInput::make('brevo_smtp_username')->label('SMTP login')
                        ->helperText('Usually the email you use to sign in to Brevo.'),
                    TextInput::make('brevo_smtp_password')->label('SMTP key')->password()->revealable()
                        ->dehydrated(fn ($state): bool => filled($state))
                        ->helperText(fn (): string => BrevoMail::hasSmtpPassword()
                            ? 'An SMTP key is already saved. Leave blank to keep it.'
                            : 'From SMTP & API → SMTP. Not the same as the API key.'),
                ])->columns(2),
            Section::make('Brevo contact lists')
                ->description('Optional. Sync new Lens users into Brevo lists (Contacts → Lists). Uses the API key.')
                ->visible($whenOn)
                ->schema([
                    Toggle::make('brevo_sync_contacts')->label('Add new users to Brevo lists'),
                    TextInput::make('brevo_clients_list_id')->label('Clients list ID')->numeric(),
                    TextInput::make('brevo_vendors_list_id')->label('Vendors list ID')->numeric(),
                    TextInput::make('brevo_staff_list_id')->label('Staff list ID')->numeric(),
                ])->columns(2),
            Section::make('Brevo transactional templates')
                ->description('Numeric template IDs from Campaigns → Templates. Leave blank to send a plain-text fallback. Used when Send through is API.')
                ->visible($whenOn)
                ->schema([
                    TextInput::make('brevo_template_welcome')->label('Welcome / new account')->numeric(),
                    TextInput::make('brevo_template_account_approved')->label('Account approved')->numeric(),
                    TextInput::make('brevo_template_account_rejected')->label('Account rejected')->numeric(),
                    TextInput::make('brevo_template_booking_created')->label('New booking request')->numeric(),
                    TextInput::make('brevo_template_booking_accepted')->label('Booking accepted')->numeric(),
                    TextInput::make('brevo_template_payment')->label('Payment / escrow held')->numeric(),
                    TextInput::make('brevo_template_booking_status')->label('Booking status change')->numeric(),
                    TextInput::make('brevo_template_booking_cancelled')->label('Booking cancelled')->numeric(),
                    TextInput::make('brevo_template_delivery')->label('Deliverables uploaded')->numeric(),
                    TextInput::make('brevo_template_revision')->label('Revision requested')->numeric(),
                    TextInput::make('brevo_template_review')->label('New review')->numeric(),
                    TextInput::make('brevo_template_offer')->label('Offers & coupons')->numeric(),
                    TextInput::make('brevo_template_payout')->label('Payout / wallet')->numeric(),
                    TextInput::make('brevo_template_password_reset')->label('Password reset')->numeric(),
                ])->columns(2),
        ];
    }

    /**
     * @return list<TextInput>
     */
    protected function portfolioQuotaInputs(): array
    {
        return VendorType::query()->orderBy('sort_order')->get()
            ->map(fn (VendorType $type): TextInput => TextInput::make('portfolio_quota_mb.'.$type->slug)
                ->label($type->name_en.' portfolio')
                ->numeric()
                ->minValue(1)
                ->suffix('MB')
                ->helperText('Total upload space for this vendor category. Default: 500 MB.'))
            ->all();
    }
}
