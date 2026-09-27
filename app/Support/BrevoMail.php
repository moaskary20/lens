<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use LogicException;
use Throwable;

class BrevoMail
{
    public const PREFIX = 'brevo_';

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'enabled' => false,
            'transport' => 'smtp',
            'api_key' => '',
            'api_base_url' => 'https://api.brevo.com/v3',
            'smtp_host' => 'smtp-relay.brevo.com',
            'smtp_port' => 587,
            'smtp_encryption' => 'tls',
            'smtp_username' => '',
            'smtp_password' => '',
            'from_email' => 'hello@lens.app',
            'from_name' => 'Lens',
            'reply_to_email' => '',
            'reply_to_name' => '',
            'bcc_email' => '',
            'track_opens' => true,
            'track_clicks' => true,
            'sync_contacts' => false,
            'clients_list_id' => '',
            'vendors_list_id' => '',
            'staff_list_id' => '',
            'webhook_secret' => '',
            'template_welcome' => '',
            'template_account_approved' => '',
            'template_account_rejected' => '',
            'template_booking_created' => '',
            'template_booking_accepted' => '',
            'template_payment' => '',
            'template_booking_status' => '',
            'template_booking_cancelled' => '',
            'template_delivery' => '',
            'template_revision' => '',
            'template_review' => '',
            'template_offer' => '',
            'template_payout' => '',
            'template_password_reset' => '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function settings(): array
    {
        $saved = Setting::groupValues('mail');
        $defaults = self::defaults();
        $merged = [];

        foreach ($defaults as $key => $default) {
            $value = $saved[$key] ?? $default;
            $merged[$key] = is_bool($default) ? (bool) $value : (is_int($default) ? (int) $value : (string) $value);
        }

        return $merged;
    }

    /**
     * @return array<string, mixed>
     */
    public static function formState(): array
    {
        $state = [];
        foreach (self::settings() as $key => $value) {
            if (in_array($key, ['api_key', 'smtp_password', 'webhook_secret'], true)) {
                $state[self::PREFIX.$key] = '';
                continue;
            }
            $state[self::PREFIX.$key] = $value;
        }

        return $state;
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    public static function splitState(array $state): array
    {
        $mail = [];
        $platform = [];

        foreach ($state as $key => $value) {
            if (str_starts_with((string) $key, self::PREFIX)) {
                $mail[substr((string) $key, strlen(self::PREFIX))] = $value;
                continue;
            }
            $platform[$key] = $value;
        }

        return [$platform, $mail];
    }

    /**
     * @param  array<string, mixed>  $mail
     * @return array<string, mixed>
     */
    public static function sanitize(array $mail): array
    {
        $current = self::settings();
        $clean = [];

        foreach (self::defaults() as $key => $default) {
            $value = $mail[$key] ?? $current[$key] ?? $default;
            if (in_array($key, ['api_key', 'smtp_password', 'webhook_secret'], true) && ! filled($mail[$key] ?? null)) {
                $value = $current[$key] ?? '';
            }
            $clean[$key] = is_bool($default) ? (bool) $value : (is_int($default) ? (int) $value : trim((string) $value));
        }

        return $clean;
    }

    public static function enabled(): bool
    {
        return (bool) self::settings()['enabled'];
    }

    public static function hasApiKey(): bool
    {
        return filled(self::settings()['api_key']);
    }

    public static function hasSmtpPassword(): bool
    {
        return filled(self::settings()['smtp_password']);
    }

    public static function hasWebhookSecret(): bool
    {
        return filled(self::settings()['webhook_secret']);
    }

    public static function apply(): void
    {
        $settings = self::settings();
        if (! $settings['enabled']) {
            return;
        }

        config([
            'mail.from.address' => $settings['from_email'] ?: config('mail.from.address'),
            'mail.from.name' => $settings['from_name'] ?: config('mail.from.name'),
            'services.brevo.key' => $settings['api_key'],
            'services.brevo.base_url' => $settings['api_base_url'] ?: 'https://api.brevo.com/v3',
        ]);

        if ($settings['transport'] !== 'smtp') {
            return;
        }

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.transport' => 'smtp',
            'mail.mailers.smtp.host' => $settings['smtp_host'] ?: 'smtp-relay.brevo.com',
            'mail.mailers.smtp.port' => (int) ($settings['smtp_port'] ?: 587),
            'mail.mailers.smtp.username' => $settings['smtp_username'] ?: $settings['from_email'],
            'mail.mailers.smtp.password' => $settings['smtp_password'] ?: $settings['api_key'],
            'mail.mailers.smtp.scheme' => $settings['smtp_encryption'] === 'ssl' ? 'smtps' : ($settings['smtp_encryption'] === 'none' ? null : 'smtp'),
        ]);
    }

    public static function sendTest(string $to): void
    {
        $settings = self::settings();
        if (! $settings['enabled']) {
            throw new LogicException('Turn on Brevo email in Platform settings first.');
        }

        self::apply();

        if ($settings['transport'] === 'api') {
            self::sendViaApi($to, 'Lens test email', 'This is a Brevo test message from Lens Platform settings.');

            return;
        }

        if (! filled($settings['smtp_password']) && ! filled($settings['api_key'])) {
            throw new LogicException('Add a Brevo SMTP key or API key before sending.');
        }

        Mail::raw('This is a Brevo SMTP test message from Lens Platform settings.', function ($message) use ($to, $settings): void {
            $message->to($to)->subject('Lens test email');
            if (filled($settings['reply_to_email'])) {
                $message->replyTo($settings['reply_to_email'], $settings['reply_to_name'] ?: null);
            }
            if (filled($settings['bcc_email'])) {
                $message->bcc($settings['bcc_email']);
            }
        });
    }

    public static function sendViaApi(string $to, string $subject, string $text, ?int $templateId = null, array $params = []): void
    {
        $settings = self::settings();
        if (! filled($settings['api_key'])) {
            throw new LogicException('Add a Brevo API key before sending with the API.');
        }

        $payload = [
            'sender' => [
                'email' => $settings['from_email'],
                'name' => $settings['from_name'] ?: 'Lens',
            ],
            'to' => [['email' => $to]],
            'subject' => $subject,
        ];

        if ($templateId) {
            $payload['templateId'] = $templateId;
            $payload['params'] = $params;
        } else {
            $payload['textContent'] = $text;
        }

        if (filled($settings['reply_to_email'])) {
            $payload['replyTo'] = [
                'email' => $settings['reply_to_email'],
                'name' => $settings['reply_to_name'] ?: $settings['from_name'],
            ];
        }

        if (filled($settings['bcc_email'])) {
            $payload['bcc'] = [['email' => $settings['bcc_email']]];
        }

        $base = rtrim($settings['api_base_url'] ?: 'https://api.brevo.com/v3', '/');

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'api-key' => $settings['api_key'],
                    'accept' => 'application/json',
                ])
                ->post($base.'/smtp/email', $payload);
        } catch (Throwable $exception) {
            throw new LogicException('Could not reach Brevo: '.$exception->getMessage(), 0, $exception);
        }

        if (! $response->successful()) {
            $message = $response->json('message') ?: $response->body() ?: 'Brevo rejected the request.';
            throw new LogicException(is_string($message) ? $message : 'Brevo rejected the request.');
        }
    }
}
