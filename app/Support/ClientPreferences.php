<?php

namespace App\Support;

class ClientPreferences
{
    /**
     * @var array<string, array{label: string, helper: string, group: string}>
     */
    public const FIELDS = [
        'push_notifications' => [
            'label' => 'Push notifications',
            'helper' => 'Bookings, messages, and account alerts.',
            'group' => 'Alerts',
        ],
        'chat_alerts' => [
            'label' => 'Chat alerts',
            'helper' => 'Notify the client when a creator replies.',
            'group' => 'Alerts',
        ],
        'message_preview' => [
            'label' => 'Message preview',
            'helper' => 'Show the first line on the lock screen.',
            'group' => 'Alerts',
        ],
        'booking_reminders' => [
            'label' => 'Booking reminders',
            'helper' => 'Remind the client before a session starts.',
            'group' => 'Alerts',
        ],
        'review_prompts' => [
            'label' => 'Review prompts',
            'helper' => 'Ask for a rating after a session is approved.',
            'group' => 'Alerts',
        ],
        'email_offers' => [
            'label' => 'Offers & deals',
            'helper' => 'Seasonal codes and first-order emails.',
            'group' => 'Alerts',
        ],
        'vibration' => [
            'label' => 'Vibration',
            'helper' => 'Vibrate with alerts on the device.',
            'group' => 'Alerts',
        ],
        'read_receipts' => [
            'label' => 'Read receipts',
            'helper' => 'Let creators see when a chat was opened.',
            'group' => 'Privacy',
        ],
        'hide_activity' => [
            'label' => 'Hide activity',
            'helper' => 'Do not show last seen on creator chats.',
            'group' => 'Privacy',
        ],
        'haptic_feedback' => [
            'label' => 'Haptic feedback',
            'helper' => 'Light tap on buttons and switches.',
            'group' => 'Display',
        ],
        'reduce_motion' => [
            'label' => 'Reduce motion',
            'helper' => 'Limit animations across the client app.',
            'group' => 'Display',
        ],
        'language' => [
            'label' => 'App language',
            'helper' => 'Synced with the Language field on this account.',
            'group' => 'Display',
        ],
    ];

    /**
     * @return array<string, string>
     */
    public static function onOff(): array
    {
        return [
            '1' => 'On',
            '0' => 'Off',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function languages(): array
    {
        return [
            'en' => 'English',
            'ar' => 'Arabic',
        ];
    }

    /**
     * @return array<string, bool|string>
     */
    public static function defaults(?string $locale = 'en'): array
    {
        return [
            'push_notifications' => Feature::enabled('notifications'),
            'chat_alerts' => Feature::enabled('chat'),
            'booking_reminders' => Feature::enabled('bookings'),
            'email_offers' => Feature::enabled('coupons'),
            'message_preview' => true,
            'vibration' => true,
            'review_prompts' => Feature::enabled('reviews'),
            'read_receipts' => Feature::enabled('chat'),
            'hide_activity' => false,
            'haptic_feedback' => true,
            'reduce_motion' => false,
            'language' => in_array($locale, ['en', 'ar'], true) ? $locale : 'en',
        ];
    }

    /**
     * @return array<string, bool|string>
     */
    public static function merge(mixed $stored, ?string $locale = 'en'): array
    {
        return self::normalize(is_array($stored) ? $stored : [], $locale);
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, bool|string>
     */
    public static function normalize(array $settings, ?string $locale = 'en'): array
    {
        $out = self::defaults($locale);

        foreach (array_keys($out) as $key) {
            if (! array_key_exists($key, $settings)) {
                continue;
            }

            if ($key === 'language') {
                $out[$key] = in_array($settings[$key], ['en', 'ar'], true) ? $settings[$key] : $out[$key];

                continue;
            }

            $out[$key] = self::bool($settings[$key]);
        }

        return $out;
    }

    /**
     * @return array<string, string>
     */
    public static function forForm(mixed $stored, ?string $locale = 'en'): array
    {
        $normalized = self::merge($stored, $locale);

        foreach ($normalized as $key => $value) {
            if ($key === 'language') {
                continue;
            }

            $normalized[$key] = $value ? '1' : '0';
        }

        return $normalized;
    }

    /**
     * @return array<string, bool|string>
     */
    public static function fromForm(mixed $settings, ?string $locale = 'en'): array
    {
        return self::normalize(is_array($settings) ? $settings : [], $locale);
    }

    public static function bool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        return in_array(strtolower((string) $value), ['1', 'true', 'on', 'yes'], true);
    }
}
