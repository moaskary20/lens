<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class SiteStats
{
    /**
     * @return array{
     *     creators: int,
     *     creators_label: string,
     *     clients: int,
     *     clients_label: string,
     *     rating: float,
     *     rating_label: string,
     *     reviews: int
     * }
     */
    public static function marketplace(): array
    {
        try {
            if (! Schema::hasTable('vendors')) {
                return self::empty();
            }
        } catch (\Throwable) {
            return self::empty();
        }

        return Cache::store('file')->remember('lens.site.stats.v1', now()->addMinutes(5), function (): array {
            $creators = (int) Vendor::query()->marketplaceVisible()->count();

            $clients = (int) Booking::query()
                ->whereIn('status', ['completed', 'delivered', 'approved'])
                ->distinct('client_id')
                ->count('client_id');

            if ($clients <= 0) {
                $clients = (int) User::query()
                    ->where('role', 'client')
                    ->where('is_active', true)
                    ->count();
            }

            $rated = Vendor::query()
                ->marketplaceVisible()
                ->where('rating_count', '>', 0)
                ->get(['rating_avg', 'rating_count']);

            $reviewWeight = (int) $rated->sum(fn (Vendor $vendor): int => (int) $vendor->rating_count);
            $rating = 0.0;
            if ($reviewWeight > 0) {
                $rating = round(
                    (float) $rated->sum(
                        fn (Vendor $vendor): float => (float) $vendor->rating_avg * (int) $vendor->rating_count
                    ) / $reviewWeight,
                    1,
                );
            }

            return [
                'creators' => $creators,
                'creators_label' => self::formatCount($creators),
                'clients' => $clients,
                'clients_label' => self::formatCount($clients),
                'rating' => $rating,
                'rating_label' => $rating > 0 ? number_format($rating, 1).' / 5' : '—',
                'reviews' => $reviewWeight,
            ];
        });
    }

    public static function formatCount(int $value): string
    {
        if ($value >= 1_000_000) {
            $n = $value / 1_000_000;

            return rtrim(rtrim(number_format($n, $n >= 10 ? 0 : 1, '.', ''), '0'), '.').'M+';
        }
        if ($value >= 1_000) {
            $n = $value / 1_000;

            return rtrim(rtrim(number_format($n, $n >= 10 ? 0 : 1, '.', ''), '0'), '.').'K+';
        }

        return (string) max(0, $value);
    }

    /**
     * @return array{
     *     creators: int,
     *     creators_label: string,
     *     clients: int,
     *     clients_label: string,
     *     rating: float,
     *     rating_label: string,
     *     reviews: int
     * }
     */
    protected static function empty(): array
    {
        return [
            'creators' => 0,
            'creators_label' => '0',
            'clients' => 0,
            'clients_label' => '0',
            'rating' => 0.0,
            'rating_label' => '—',
            'reviews' => 0,
        ];
    }
}
