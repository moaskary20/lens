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

    protected static ?string $title = 'Notification emails and events';

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
                ->label('Send notice')
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
                    Notification::make()->title("Sent to {$count} client inbox".($count === 1 ? '' : 'es').(LensNotifier::emailEnabled() ? ' and email' : ''))->success()->send();
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
                Section::make('Email delivery')
                    ->description('Every enabled event below also goes out as email when this is on. Sending uses the Brevo connection in Platform settings.')
                    ->schema([
                        Toggle::make('email_enabled')
                            ->label('Send notification emails')
                            ->helperText('Inbox alerts stay in the app. Turn this on so the same alert is emailed too.')
                            ->live()
                            ->default(true),
                    ]),
                Section::make('Message emails')
                    ->description('Chat alerts when a client or vendor posts in a booking conversation.')
                    ->visible(fn (Get $get): bool => (bool) $get('email_enabled'))
                    ->schema([
                        Toggle::make('email_messages')
                            ->label('Email new messages')
                            ->helperText('Off keeps chat in the app inbox only.')
                            ->live()
                            ->default(true),
                        Toggle::make('email_message_preview')
                            ->label('Include message preview')
                            ->helperText('Show the first lines of the chat in the email. Off sends “You have a new message on Lens.”')
                            ->visible(fn (Get $get): bool => (bool) $get('email_messages'))
                            ->default(true),
                    ]),
                Section::make('Events')
                    ->description('Alerts land in the admin and vendor bells, the client app inbox, and email when Email delivery is on. Turn an event off without deleting history.')
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
                        Action::make('save')->label('Save notification settings')->submit('save'),
                    ]),
                ]),
        ]);
    }

    public function save(): void
    {
        $state = array_merge(LensNotifier::defaults(), $this->form->getState(), $this->data);
        $values = [];
        foreach (LensNotifier::defaults() as $key => $default) {
            $values[$key] = (bool) ($state[$key] ?? $default);
        }

        Setting::setGroupValues('notifications', $values);

        Notification::make()->title('Notification settings saved')->success()->send();
    }
}
