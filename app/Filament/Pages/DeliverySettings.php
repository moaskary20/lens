<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Support\DeliveryProtection;
use App\Support\Feature;
use App\Support\Roles;
use BackedEnum;
use Filament\Actions\Action;
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

class DeliverySettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?string $navigationLabel = 'Protected delivery';

    protected static ?string $title = 'Secure file delivery & preview protection';

    protected static string|UnitEnum|null $navigationGroup = 'Bookings & Finance';

    protected static ?int $navigationSort = 6;

    /**
     * @var array<string, mixed>
     */
    public array $data = [];

    public static function canAccess(): bool
    {
        return Roles::staffCan(auth()->user(), 'manage_settings') && Feature::enabled('protected_delivery');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function mount(): void
    {
        $this->form->fill(DeliveryProtection::settings());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('5.1 Protected preview stream')
                    ->description('Vendors upload inside Lens. Clients only see a protected preview until they approve.')
                    ->schema([
                        Toggle::make('watermark_enabled')->label('Apply Lens watermark on previews'),
                        TextInput::make('watermark_text')->label('Watermark text'),
                        Toggle::make('anti_screenshot')->label('Anti-screenshot overlay'),
                        Toggle::make('block_recording')->label('Block screen recording in the app'),
                        Toggle::make('block_download_until_approval')->label('Disable original download until Approve'),
                        Toggle::make('preview_overlay')->label('Show protected previewer overlay'),
                        Toggle::make('revision_opens_chat')->label('Open in-app chat when a revision is requested'),
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
                        Action::make('save')->label('Save delivery protection')->submit('save'),
                    ]),
                ]),
        ]);
    }

    public function save(): void
    {
        Setting::setGroupValues('delivery', $this->form->getState());

        Notification::make()->title('Delivery protection saved')->success()->send();
    }
}
