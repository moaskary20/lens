<?php

namespace App\Services;

use App\Models\DeviceToken;
use App\Models\User;
use App\Support\ClientPreferences;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmPush
{
    public function enabled(): bool
    {
        return $this->credentials() !== null || filled(config('services.fcm.server_key'));
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
        if (($data['event'] ?? null) === 'message' && ! ($settings['chat_alerts'] ?? true)) {
            return 0;
        }

        $tokens = DeviceToken::query()
            ->where('user_id', $user->id)
            ->pluck('token')
            ->filter(fn ($token): bool => is_string($token) && $token !== '' && ! str_starts_with($token, 'lens-local-'))
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
        if ($token === '') {
            return false;
        }

        if ($this->credentials() !== null) {
            return $this->sendHttpV1($token, $title, $body, $data);
        }

        return $this->sendLegacy($token, $title, $body, $data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function sendHttpV1(string $token, string $title, string $body, array $data = []): bool
    {
        $credentials = $this->credentials();
        if ($credentials === null) {
            return false;
        }

        $accessToken = $this->accessToken($credentials);
        if ($accessToken === null) {
            return false;
        }

        $projectId = (string) ($credentials['project_id'] ?? '');
        if ($projectId === '') {
            return false;
        }

        $payloadData = array_merge([
            'title' => $title,
            'body' => $body,
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
        ], array_map(
            fn ($value) => is_scalar($value) ? (string) $value : (string) json_encode($value),
            $data,
        ));

        try {
            $response = Http::withToken($accessToken)
                ->acceptJson()
                ->post('https://fcm.googleapis.com/v1/projects/'.$projectId.'/messages:send', [
                    'message' => [
                        'token' => $token,
                        'notification' => [
                            'title' => $title,
                            'body' => $body,
                        ],
                        'android' => [
                            'priority' => 'HIGH',
                            'notification' => [
                                'channel_id' => 'lens_alerts_v2',
                                'sound' => 'default',
                                'default_vibrate_timings' => true,
                                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                            ],
                        ],
                        'apns' => [
                            'payload' => [
                                'aps' => [
                                    'sound' => 'default',
                                ],
                            ],
                        ],
                        'data' => $payloadData,
                    ],
                ]);

            if (! $response->successful()) {
                Log::warning('FCM HTTP v1 push failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                $bodyText = $response->body();
                if (
                    str_contains($bodyText, 'UNREGISTERED')
                    || str_contains($bodyText, 'NOT_FOUND')
                    || str_contains($bodyText, 'INVALID_ARGUMENT')
                ) {
                    DeviceToken::query()->where('token', $token)->delete();
                }

                return false;
            }

            return true;
        } catch (\Throwable $exception) {
            Log::warning('FCM HTTP v1 exception', ['message' => $exception->getMessage()]);

            return false;
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function sendLegacy(string $token, string $title, string $body, array $data = []): bool
    {
        $key = (string) config('services.fcm.server_key');
        if ($key === '') {
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
                    'android_channel_id' => 'lens_alerts_v2',
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

    /**
     * @return array{type?: string, project_id?: string, private_key?: string, client_email?: string}|null
     */
    protected function credentials(): ?array
    {
        $path = (string) config('services.fcm.credentials');
        if ($path === '') {
            return null;
        }

        if (! str_starts_with($path, '/')) {
            $path = base_path($path);
        }

        if (! is_readable($path)) {
            return null;
        }

        /** @var array{type?: string, project_id?: string, private_key?: string, client_email?: string}|null $decoded */
        $decoded = json_decode((string) file_get_contents($path), true);
        if (! is_array($decoded)) {
            return null;
        }

        if (
            empty($decoded['project_id'])
            || empty($decoded['client_email'])
            || empty($decoded['private_key'])
        ) {
            return null;
        }

        return $decoded;
    }

    /**
     * @param  array{project_id?: string, private_key?: string, client_email?: string}  $credentials
     */
    protected function accessToken(array $credentials): ?string
    {
        $cacheKey = 'lens.fcm.access_token.'.md5((string) ($credentials['client_email'] ?? ''));

        return Cache::remember($cacheKey, now()->addMinutes(50), function () use ($credentials): ?string {
            $jwt = $this->serviceAccountJwt($credentials);
            if ($jwt === null) {
                return null;
            }

            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            if (! $response->successful()) {
                Log::warning('FCM OAuth token failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $token = $response->json('access_token');

            return is_string($token) && $token !== '' ? $token : null;
        });
    }

    /**
     * @param  array{private_key?: string, client_email?: string}  $credentials
     */
    protected function serviceAccountJwt(array $credentials): ?string
    {
        $email = (string) ($credentials['client_email'] ?? '');
        $privateKey = (string) ($credentials['private_key'] ?? '');
        if ($email === '' || $privateKey === '') {
            return null;
        }

        $now = time();
        $header = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR));
        $claims = $this->base64UrlEncode(json_encode([
            'iss' => $email,
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ], JSON_THROW_ON_ERROR));

        $unsigned = $header.'.'.$claims;
        $key = openssl_pkey_get_private($privateKey);
        if ($key === false) {
            Log::warning('FCM service account private key is invalid.');

            return null;
        }

        $signature = '';
        if (! openssl_sign($unsigned, $signature, $key, OPENSSL_ALGO_SHA256)) {
            Log::warning('FCM JWT signing failed.');

            return null;
        }

        return $unsigned.'.'.$this->base64UrlEncode($signature);
    }

    protected function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
