<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Support\Feature;
use App\Support\Roles;
use BackedEnum;
use Filament\Actions\Action;
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

class FeatureSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static ?string $navigationLabel = 'Optional features';

    protected static ?string $title = 'Enable or disable platform features';

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 1;

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
        $this->form->fill(array_merge(Feature::defaults(), Setting::groupValues('features')));
    }

    public function form(Schema $schema): Schema
    {
        $toggles = [];

        foreach (Feature::CATALOG as $key => $meta) {
            $toggles[] = Toggle::make($key)
                ->label($meta['label'])
                ->helperText($meta['description'])
                ->default($meta['default']);
        }

        return $schema
            ->components([
                Section::make('Every feature can be turned off without deleting its data')
                    ->description('Disabled features disappear from the sidebar and stay hidden in the future app.')
                    ->schema($toggles)
                    ->columns(2),
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
                        Action::make('save')
                            ->label('Save features')
                            ->submit('save'),
                    ]),
                ]),
        ]);
    }

    public function save(): void
    {
        Setting::setGroupValues('features', $this->form->getState());

        Notification::make()->title('Features updated')->success()->send();
    }
}
