<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Models\VendorType;
use App\Support\Roles;
use App\Support\StorageQuota;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
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
        ], Setting::groupValues('platform'), StorageQuota::settings()));
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
        Setting::setGroupValues('platform', $this->form->getState());

        Notification::make()->title('Platform settings saved')->success()->send();
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
