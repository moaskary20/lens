<?php

namespace App\Support;

use App\Models\Setting;

class AppUpdate
{
    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'force_update' => false,
            'android_min_version' => '',
            'ios_min_version' => '',
            'android_store_url' => 'https://play.google.com/store/apps/details?id=app.lens.lens',
            'ios_store_url' => '',
            'update_title' => 'Update required',
            'update_message' => 'A new version of Lens is available on the store. Please update before continuing.',
        ];
    }

    /**
     * Payload consumed by the mobile app on every cold/warm open.
     *
     * @return array{
     *     force_update: bool,
     *     android_min_version: string,
     *     ios_min_version: string,
     *     android_store_url: string,
     *     ios_store_url: string,
     *     title: string,
     *     message: string
     * }
     */
    public static function forBootstrap(): array
    {
        $saved = Setting::groupValues('platform');
        $defaults = self::defaults();

        $androidStore = trim((string) ($saved['android_store_url'] ?? ''));
        $iosStore = trim((string) ($saved['ios_store_url'] ?? ''));

        return [
            'force_update' => (bool) ($saved['force_update'] ?? $defaults['force_update']),
            'android_min_version' => trim((string) ($saved['android_min_version'] ?? $defaults['android_min_version'])),
            'ios_min_version' => trim((string) ($saved['ios_min_version'] ?? $defaults['ios_min_version'])),
            'android_store_url' => $androidStore !== '' ? $androidStore : $defaults['android_store_url'],
            'ios_store_url' => $iosStore !== '' ? $iosStore : $defaults['ios_store_url'],
            'title' => trim((string) ($saved['update_title'] ?? $defaults['update_title'])) ?: $defaults['update_title'],
            'message' => trim((string) ($saved['update_message'] ?? $defaults['update_message'])) ?: $defaults['update_message'],
        ];
    }
}
