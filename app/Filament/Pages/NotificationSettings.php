<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Support\Feature;
use App\Support\LensNotifier;
use App\Support\Roles;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
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

class NotificationSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBell;

    protected static ?string $navigationLabel = 'Notification events';

    protected static ?string $title = 'Which in-app alerts to send';

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'notification-settings';

    /**
     * @var array<string, mixed>
     */
    public array $data = [];

    public static function canAccess(): bool
    {
        return Roles::staffCan(auth()->user(), 'manage_settings')
            && Feature::enabled('notifications');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('send')
                ->label('Send to the app')
                ->icon(Heroicon::OutlinedPaperAirplane)
                ->form([
                    TextInput::make('title')->label('Title')->required()->maxLength(120),
                    Textarea::make('body')->label('Message')->required()->rows(4)->maxLength(500),
                    Select::make('audience')->label('Audience')->options([
                        'all' => 'All clients',
                        'one' => 'One client',
                    ])->default('all')->live()->required(),
                    Select::make('user_id')->label('Client')
                        ->options(fn (): array => User::query()->where('role', 'client')->where('is_active', true)->orderBy('name')->pluck('email', 'id')->all())
                        ->searchable()
                        ->visible(fn (Get $get): bool => $get('audience') === 'one')
                        ->required(fn (Get $get): bool => $get('audience') === 'one'),
                ])
                ->action(function (array $data): void {
                    $title = (string) $data['title'];
                    $body = (string) $data['body'];
                    if (($data['audience'] ?? 'all') === 'one') {
                        $user = User::query()->find($data['user_id'] ?? 0);
                        $count = $user ? LensNotifier::toClients($title, $body, [$user]) : 0;
                    } else {
                        $count = LensNotifier::toClients($title, $body);
                    }
                    Notification::make()->title("Sent to {$count} client inbox".($count === 1 ? '' : 'es'))->success()->send();
                }),
        ];
    }

    public function mount(): void
    {
        $this->form->fill(array_merge(LensNotifier::defaults(), Setting::groupValues('notifications')));
    }

    public function form(Schema $schema): Schema
    {
        $toggles = [];

        foreach (LensNotifier::EVENTS as $key => $meta) {
            $toggles[] = Toggle::make($key)
                ->label($meta['label'])
                ->helperText($meta['description'])
                ->default($meta['default']);
        }

        return $schema
            ->components([
                Section::make('Events')
                    ->description('Alerts land in the admin and vendor bells and in the client app inbox. Turn an event off without deleting history.')
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
                        Action::make('save')->label('Save notification events')->submit('save'),
                    ]),
                ]),
        ]);
    }

    public function save(): void
    {
        Setting::setGroupValues('notifications', $this->form->getState());

        Notification::make()->title('Notification events saved')->success()->send();
    }
}
