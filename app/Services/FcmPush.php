<?php

namespace App\Services;

use App\Models\DeviceToken;
use App\Models\User;
use App\Support\ClientPreferences;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmPush
{
    public function enabled(): bool
    {
        return filled(config('services.fcm.server_key'));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function sendToUser(User $user, string $title, string $body, array $data = []): int
    {
        if (! $this->enabled()) {
            return 0;
        }

        $settings = ClientPreferences::merge($user->app_settings, $user->locale ?: 'en');
        if (! ($settings['push_notifications'] ?? true)) {
            return 0;
        }

        $tokens = DeviceToken::query()
            ->where('user_id', $user->id)
            ->pluck('token')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($tokens === []) {
            return 0;
        }

        $sent = 0;
        foreach ($tokens as $token) {
            if ($this->send($token, $title, $body, $data)) {
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function send(string $token, string $title, string $body, array $data = []): bool
    {
        $key = (string) config('services.fcm.server_key');
        if ($key === '' || $token === '') {
            return false;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'key='.$key,
                'Content-Type' => 'application/json',
            ])->post('https://fcm.googleapis.com/fcm/send', [
                'to' => $token,
                'priority' => 'high',
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                    'sound' => 'default',
                    'android_channel_id' => 'lens_alerts',
                ],
                'data' => array_merge([
                    'title' => $title,
                    'body' => $body,
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                ], array_map(fn ($value) => is_scalar($value) ? (string) $value : json_encode($value), $data)),
            ]);

            if (! $response->successful()) {
                Log::warning('FCM push failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                if (str_contains($response->body(), 'NotRegistered') || str_contains($response->body(), 'InvalidRegistration')) {
                    DeviceToken::query()->where('token', $token)->delete();
                }

                return false;
            }

            return true;
        } catch (\Throwable $exception) {
            Log::warning('FCM push exception', ['message' => $exception->getMessage()]);

            return false;
        }
    }
}
