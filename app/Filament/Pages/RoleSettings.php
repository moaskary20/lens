<?php

namespace App\Filament\Pages;

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
            'admin' => Roles::capabilitiesFor('admin'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        $sections = [
            Section::make('How roles work with Optional features')
                ->description('Optional features switch a module on for Lens. These toggles decide which role may use a module that is on. Vendor types stay under Optional features. Replacement offers were removed; cancellation penalties remain under cancellation policies.')
                ->schema([
                    Placeholder::make('roles_help')
                        ->hiddenLabel()
                        ->content('Client and vendor flags drive the mobile app (book, chat, disputes, Report an Issue, wallet, AI). Supervisor flags drive the admin desks (disputes, app issues, chat, escrow, wallets, payouts). Platform admins always keep every staff capability.'),
                ]),
        ];

        foreach (['client', 'vendor', 'supervisor'] as $role) {
            $meta = Roles::CATALOG[$role];
            $groups = [];

            foreach (Roles::groupedCatalog($role) as $group => $capabilities) {
                $toggles = [];
                foreach ($capabilities as $key => $capability) {
                    $toggles[] = Toggle::make("{$role}.{$key}")
                        ->label($capability['label'])
                        ->helperText($capability['description']);
                }

                $groups[] = Section::make(Roles::GROUPS[$group] ?? $group)
                    ->schema($toggles)
                    ->columns(2);
            }

            $sections[] = Section::make($meta['label'])
                ->description($meta['description'])
                ->schema($groups)
                ->collapsible();
        }

        $admin = Roles::CATALOG['admin'];
        $adminGroups = [];
        foreach (Roles::groupedCatalog('admin') as $group => $capabilities) {
            $toggles = [];
            foreach ($capabilities as $key => $capability) {
                $toggles[] = Toggle::make("admin.{$key}")
                    ->label($capability['label'])
                    ->helperText($capability['description'])
                    ->disabled()
                    ->dehydrated(false);
            }

            $adminGroups[] = Section::make(Roles::GROUPS[$group] ?? $group)
                ->schema($toggles)
                ->columns(2);
        }

        $sections[] = Section::make($admin['label'])
            ->description($admin['description'])
            ->schema($adminGroups)
            ->collapsible();

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
            Roles::persist($role, $state[$role] ?? []);
        }

        Notification::make()->title('Role capabilities saved')->success()->send();
    }
}
