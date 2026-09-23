<?php

namespace App\Notifications;

use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LensAlert extends Notification
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $body,
        public string $event = 'general',
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $payload = FilamentNotification::make()
            ->title($this->title)
            ->body($this->body)
            ->getDatabaseMessage();

        $payload['event'] = $this->event;

        return $payload;
    }
}
