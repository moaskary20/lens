<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Support\Roles;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
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

class RoleSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static ?string $navigationLabel = 'User roles';

    protected static ?string $title = 'User roles and capabilities';

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 0;

    /**
     * @var array<string, mixed>
     */
    public array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public function mount(): void
    {
        $this->form->fill([
            'client' => Roles::capabilitiesFor('client'),
            'vendor' => Roles::capabilitiesFor('vendor'),
            'supervisor' => Roles::capabilitiesFor('supervisor'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        $sections = [];

        foreach (['client', 'vendor', 'supervisor'] as $role) {
            $meta = Roles::CATALOG[$role];
            $toggles = [];

            foreach ($meta['capabilities'] as $key => $capability) {
                $toggles[] = Toggle::make("{$role}.{$key}")
                    ->label($capability['label'])
                    ->helperText($capability['description']);
            }

            $sections[] = Section::make($meta['label'])
                ->description($meta['description'])
                ->schema($toggles)
                ->columns(2);
        }

        $sections[] = Section::make(Roles::CATALOG['admin']['label'])
            ->description(Roles::CATALOG['admin']['description'])
            ->schema([
                Placeholder::make('admin_note')
                    ->hiddenLabel()
                    ->content('Platform admins always have every staff capability: verification, disputes, refund overrides, policy exceptions, and settings. Vendor types (Photographers, Videographers, Reels, Studios, Models, UGC, Food Stylists) are toggled under Optional features.'),
            ]);

        return $schema->components($sections)->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')->label('Save role capabilities')->submit('save'),
                    ]),
                ]),
        ]);
    }

    public function save(): void
    {
        $state = $this->form->getState();

        foreach (['client', 'vendor', 'supervisor'] as $role) {
            Setting::setValue("roles.{$role}", $state[$role] ?? []);
        }

        Notification::make()->title('Role capabilities saved')->success()->send();
    }
}
