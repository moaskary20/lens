<?php

namespace App\Notifications;

use App\Support\LensNotifier;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
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
        $channels = ['database'];

        if (LensNotifier::shouldEmail($this->event, $notifiable instanceof \App\Models\User ? $notifiable : null)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $body = $this->body;
        if ($this->event === LensNotifier::MESSAGE && ! LensNotifier::messagePreviewEnabled()) {
            $body = 'You have a new message on Lens.';
        }

        return (new MailMessage)
            ->subject($this->title)
            ->greeting($this->title)
            ->line($body);
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
