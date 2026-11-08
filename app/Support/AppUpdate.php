<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AppUpdate
{
    public const ANDROID_PACKAGE = 'app.lens.lens';

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'force_update' => true,
            'android_min_version' => '',
            'ios_min_version' => '',
            'android_store_url' => 'https://play.google.com/store/apps/details?id='.self::ANDROID_PACKAGE,
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
     *     android_store_version: string|null,
     *     ios_store_version: string|null,
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
        $androidStore = $androidStore !== '' ? $androidStore : $defaults['android_store_url'];
        $iosStore = $iosStore !== '' ? $iosStore : $defaults['ios_store_url'];

        $androidConfigured = trim((string) ($saved['android_min_version'] ?? $defaults['android_min_version']));
        $iosConfigured = trim((string) ($saved['ios_min_version'] ?? $defaults['ios_min_version']));

        $androidStoreVersion = self::playStoreVersion(self::packageIdFromUrl($androidStore) ?: self::ANDROID_PACKAGE);
        $iosStoreVersion = self::appStoreVersion($iosStore);

        $androidMin = self::highestVersion($androidConfigured, $androidStoreVersion);
        $iosMin = self::highestVersion($iosConfigured, $iosStoreVersion);

        // Force whenever a minimum/store version exists unless admin explicitly turns it off.
        $forceSaved = array_key_exists('force_update', $saved)
            ? (bool) $saved['force_update']
            : (bool) $defaults['force_update'];

        return [
            'force_update' => $forceSaved,
            'android_min_version' => $androidMin,
            'ios_min_version' => $iosMin,
            'android_store_version' => $androidStoreVersion,
            'ios_store_version' => $iosStoreVersion,
            'android_store_url' => $androidStore,
            'ios_store_url' => $iosStore,
            'title' => trim((string) ($saved['update_title'] ?? $defaults['update_title'])) ?: $defaults['update_title'],
            'message' => trim((string) ($saved['update_message'] ?? $defaults['update_message'])) ?: $defaults['update_message'],
        ];
    }

    public static function highestVersion(?string ...$versions): string
    {
        $best = '';
        foreach ($versions as $version) {
            $version = trim((string) $version);
            if ($version === '') {
                continue;
            }
            if ($best === '' || self::compareVersions($version, $best) > 0) {
                $best = $version;
            }
        }

        return $best;
    }

    public static function compareVersions(string $left, string $right): int
    {
        $parts = static function (string $value): array {
            return array_map(
                static fn (string $part): int => (int) $part,
                array_values(array_filter(preg_split('/[^0-9]+/', $value) ?: [], static fn ($part) => $part !== '')),
            );
        };

        $a = $parts($left);
        $b = $parts($right);
        $len = max(count($a), count($b));
        for ($i = 0; $i < $len; $i++) {
            $l = $a[$i] ?? 0;
            $r = $b[$i] ?? 0;
            if ($l !== $r) {
                return $l <=> $r;
            }
        }

        return 0;
    }

    public static function packageIdFromUrl(string $url): ?string
    {
        if (preg_match('/[?&]id=([a-zA-Z0-9._]+)/', $url, $match)) {
            return $match[1];
        }

        return null;
    }

    public static function playStoreVersion(string $packageId): ?string
    {
        $packageId = trim($packageId);
        if ($packageId === '') {
            return null;
        }

        $cacheKey = 'lens.app_update.play.'.$packageId;

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($packageId): ?string {
            try {
                $response = Http::timeout(4)
                    ->withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (Linux; Android 13) AppleWebKit/537.36 Chrome/120.0.0.0 Mobile Safari/537.36',
                        'Accept-Language' => 'en-US,en;q=0.9',
                    ])
                    ->get('https://play.google.com/store/apps/details', [
                        'id' => $packageId,
                        'hl' => 'en',
                        'gl' => 'US',
                    ]);

                if (! $response->successful()) {
                    return null;
                }

                $html = $response->body();
                if (preg_match('/\[\[\["([\d]+(?:\.[\d]+){1,3})"\]\]/', $html, $match)) {
                    return $match[1];
                }
                if (preg_match('/Current Version<\/div><span[^>]*><div[^>]*><span[^>]*>([^<]+)</i', $html, $match)) {
                    return trim(html_entity_decode($match[1]));
                }
            } catch (\Throwable $exception) {
                Log::debug('Play Store version lookup failed', [
                    'package' => $packageId,
                    'error' => $exception->getMessage(),
                ]);
            }

            return null;
        });
    }

    public static function appStoreVersion(string $storeUrl): ?string
    {
        $storeUrl = trim($storeUrl);
        if ($storeUrl === '') {
            return null;
        }

        $appId = null;
        if (preg_match('/id(\d+)/', $storeUrl, $match)) {
            $appId = $match[1];
        }
        if ($appId === null) {
            return null;
        }

        $cacheKey = 'lens.app_update.ios.'.$appId;

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($appId): ?string {
            try {
                $response = Http::timeout(4)
                    ->acceptJson()
                    ->get('https://itunes.apple.com/lookup', ['id' => $appId]);
                if (! $response->successful()) {
                    return null;
                }
                $version = data_get($response->json(), 'results.0.version');

                return is_string($version) && $version !== '' ? $version : null;
            } catch (\Throwable $exception) {
                Log::debug('App Store version lookup failed', [
                    'id' => $appId,
                    'error' => $exception->getMessage(),
                ]);
            }

            return null;
        });
    }
}
