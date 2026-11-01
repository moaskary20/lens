<?php

namespace App\Notifications\Channels;

use App\Models\User;
use App\Notifications\LensAlert;
use App\Services\FcmPush;
use Illuminate\Notifications\Notification;

class FcmChannel
{
    public function __construct(protected FcmPush $fcm) {}

    public function send(object $notifiable, Notification $notification): void
    {
        if (! $notifiable instanceof User || ! $notification instanceof LensAlert) {
            return;
        }

        if (! $this->fcm->enabled()) {
            return;
        }

        $this->fcm->sendToUser(
            $notifiable,
            $notification->title,
            $notification->body,
            [
                'event' => $notification->event,
            ],
        );
    }
}
