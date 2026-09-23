<?php

namespace App\Filament\Resources\NotificationResource\Pages;

use App\Filament\Resources\NotificationResource;
use App\Models\User;
use App\Support\LensNotifier;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;

class ListNotifications extends ListRecords
{
    protected static string $resource = NotificationResource::class;

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
}
