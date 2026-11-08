<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $fillable = [
        'conversation_id', 'sender_id', 'body', 'type', 'attachments', 'is_flagged', 'read_at',
    ];

    protected function casts(): array
    {
        return [
            'is_flagged' => 'boolean',
            'read_at' => 'datetime',
            'attachments' => 'array',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    protected static function booted(): void
    {
        static::created(function (Message $message): void {
            $conversation = $message->conversation()->with(['client', 'vendor.user', 'booking'])->first();
            $bodyText = trim((string) $message->body);
            if (! $conversation || $bodyText === '') {
                return;
            }

            $message->loadMissing('sender');
            $preview = \Illuminate\Support\Str::limit($bodyText, 80);
            $title = 'New message';
            $body = ($message->sender?->name ?: 'Someone').' wrote: '.$preview;
            $senderId = (int) $message->sender_id;
            $payload = array_filter([
                'conversation_id' => (string) $conversation->id,
                'booking_id' => $conversation->booking_id ? (string) $conversation->booking_id : null,
            ], fn ($value) => filled($value));

            if ($conversation->client && (int) $conversation->client_id !== $senderId) {
                self::notifyRecipient($conversation->client, $title, $body, $payload);
            }

            $vendorUser = $conversation->vendor?->user;
            if ($vendorUser && (int) $vendorUser->id !== $senderId) {
                self::notifyRecipient($vendorUser, $title, $body, $payload);
            }
        });
    }

    /**
     * @param  array<string, string>  $data
     */
    protected static function notifyRecipient(User $user, string $title, string $body, array $data = []): void
    {
        $settings = \App\Support\ClientPreferences::merge($user->app_settings, $user->locale ?: 'en');
        if (! ($settings['chat_alerts'] ?? true)) {
            return;
        }

        \App\Support\LensNotifier::toUser($user, \App\Support\LensNotifier::MESSAGE, $title, $body, $data);
    }
}
