<?php

namespace App\Services;

use App\Models\City;
use App\Models\Vendor;
use App\Support\SearchEngine;
use App\Support\SearchQuery;
use Carbon\Carbon;

class VendorSearch
{
    /**
     * @return array{vendors: list<array<string, mixed>>, total: int}
     */
    public function search(SearchQuery $query): array
    {
        $settings = SearchEngine::settings();
        $vendors = Vendor::query()
            ->where('is_active', true)
            ->with(['vendorType', 'city', 'categories', 'filterTags', 'availabilities', 'badges'])
            ->get();

        $scored = $vendors
            ->map(fn (Vendor $vendor): ?array => $this->score($vendor, $query, $settings))
            ->filter()
            ->sortByDesc('score')
            ->values();

        return [
            'vendors' => $scored->take($query->limit)->all(),
            'total' => $scored->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    public function present(array $row): array
    {
        $vendor = $row['vendor'];
        $vendor->loadMissing(['vendorType', 'city', 'badges']);

        return [
            'id' => $vendor->id,
            'display_name' => $vendor->display_name,
            'vendor_type' => $vendor->vendorType?->slug,
            'vendor_type_name' => $vendor->vendorType?->name_en,
            'city' => $vendor->city?->name_en,
            'latitude' => $vendor->latitude,
            'longitude' => $vendor->longitude,
            'rating_avg' => (float) $vendor->rating_avg,
            'rating_count' => (int) $vendor->rating_count,
            'is_featured' => (bool) $vendor->is_featured,
            'badges' => $vendor->badges->pluck('slug')->values()->all(),
            'completed_sessions' => $vendor->completed_sessions,
            'half_day_price' => $vendor->half_day_price,
            'full_day_price' => $vendor->full_day_price,
            'hourly_price' => $vendor->hourly_price,
            'per_video_price' => $vendor->per_video_price,
            'score' => $row['score'],
            'matched_tags' => $row['matched_tags'] ?? [],
            'reasons' => $row['reasons'] ?? [],
        ];
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>|null
     */
    protected function score(Vendor $vendor, SearchQuery $query, array $settings): ?array
    {
        $reasons = [];
        $score = 0.0;

        if ($query->verifiedOnly && $vendor->verification_status !== 'verified') {
            return null;
        }

        if ($query->topRated && (float) $vendor->rating_avg < (float) ($settings['top_rated_threshold'] ?? 4.8)) {
            return null;
        }

        if ($query->vendorTypeSlugs !== []) {
            $slug = $vendor->vendorType?->slug;
            if (! $slug || ! in_array($slug, $query->vendorTypeSlugs, true)) {
                return null;
            }
            $score += (float) $settings['weight_vendor_type'];
            $reasons[] = 'Creator type matches';
        }

        if ($query->categorySlugs !== []) {
            $vendorCategories = $vendor->categories->pluck('slug')->all();
            $hits = array_values(array_intersect($vendorCategories, $query->categorySlugs));
            if ($hits === []) {
                return null;
            }
            $score += (float) $settings['weight_category'];
            $reasons[] = 'Category: '.implode(', ', $hits);
        }

        if (! $this->matchesLocation($vendor, $query, $settings, $score, $reasons)) {
            return null;
        }

        if (! $this->matchesPrice($vendor, $query, $reasons)) {
            return null;
        }

        $matchedTags = [];
        if ($query->filterTagSlugs !== []) {
            $vendorTags = $vendor->filterTags->pluck('slug')->all();
            $matchedTags = array_values(array_intersect($vendorTags, $query->filterTagSlugs));
            if ($matchedTags === []) {
                return null;
            }
            $ratio = count($matchedTags) / max(1, count($query->filterTagSlugs));
            $score += (float) $settings['weight_tags'] * $ratio;
            $reasons[] = 'Equipment / features: '.implode(', ', $matchedTags);
        }

        if ($query->availableOn || $query->availability) {
            if (! $this->isAvailable($vendor, $query)) {
                return null;
            }
            $score += (float) $settings['weight_availability'];
            $reasons[] = 'Calendar has an open slot';
        }

        if ($settings['rank_verified'] && $vendor->verification_status === 'verified') {
            $score += 3;
        }

        if ($settings['rank_reviews'] ?? true) {
            $score += (float) $settings['weight_rating'] * min(1, ((float) $vendor->rating_avg) / 5);
            if ($vendor->rating_count > 0) {
                $reasons[] = 'Client rating '.$vendor->rating_avg.' from '.$vendor->rating_count.' reviews';
            }
        }

        if ($settings['rank_completed_sessions'] ?? true) {
            $score += (float) $settings['weight_completed_sessions'] * min(1, $vendor->completed_sessions / 50);
        }

        if (($settings['rank_featured'] ?? true) && $vendor->is_featured) {
            $score += (float) ($settings['weight_featured'] ?? 8);
            $reasons[] = 'Featured placement';
        }

        if ($settings['rank_badges'] ?? true) {
            $slugs = $vendor->badges->pluck('slug')->all();
            if (in_array('top-rated', $slugs, true)) {
                $score += (float) ($settings['weight_badges'] ?? 5);
                $reasons[] = 'Top Rated badge';
            } elseif (in_array('popular', $slugs, true)) {
                $score += (float) ($settings['weight_badges'] ?? 5) * 0.7;
                $reasons[] = 'Popular badge';
            }
        }

        return [
            'vendor' => $vendor,
            'score' => round($score, 2),
            'matched_tags' => $matchedTags,
            'reasons' => $reasons,
        ];
    }

    /**
     * @param  array<string, mixed>  $settings
     * @param  list<string>  $reasons
     */
    protected function matchesLocation(Vendor $vendor, SearchQuery $query, array $settings, float &$score, array &$reasons): bool
    {
        $lat = $query->latitude;
        $lng = $query->longitude;
        $radius = $query->radiusKm;

        if ($query->cityId && ! $lat && ! $lng) {
            $city = City::query()->find($query->cityId);
            if ($city?->latitude && $city->longitude && $settings['primary_location']) {
                $lat = (float) $city->latitude;
                $lng = (float) $city->longitude;
                $radius ??= (float) explode(',', (string) $settings['geo_radii'])[0];
            } elseif ((int) $vendor->city_id === (int) $query->cityId) {
                $score += (float) $settings['weight_location'];
                $reasons[] = 'Same governorate';

                return true;
            } else {
                return false;
            }
        }

        if ($lat && $lng && $radius && $vendor->latitude && $vendor->longitude) {
            $distance = $this->distanceKm($lat, $lng, (float) $vendor->latitude, (float) $vendor->longitude);
            if ($distance > $radius) {
                return false;
            }
            $score += (float) $settings['weight_location'] * max(0, 1 - ($distance / $radius));
            $reasons[] = round($distance, 1).' km away';

            return true;
        }

        if ($query->cityId && (int) $vendor->city_id !== (int) $query->cityId) {
            return false;
        }

        if ($query->cityId) {
            $score += (float) $settings['weight_location'];
            $reasons[] = 'Same governorate';
        }

        return true;
    }

    /**
     * @param  list<string>  $reasons
     */
    protected function matchesPrice(Vendor $vendor, SearchQuery $query, array &$reasons): bool
    {
        $price = $this->vendorPrice($vendor, $query->packageType);

        if ($price === null) {
            return $query->minPrice === null && $query->maxPrice === null && $query->packageType === null;
        }

        if ($query->minPrice !== null && $price < $query->minPrice) {
            return false;
        }

        if ($query->maxPrice !== null && $price > $query->maxPrice) {
            return false;
        }

        if ($query->minPrice !== null || $query->maxPrice !== null || $query->packageType) {
            $reasons[] = 'Within budget at '.$price;
        }

        return true;
    }

    protected function vendorPrice(Vendor $vendor, ?string $packageType): ?float
    {
        $map = [
            'half_day' => $vendor->half_day_price,
            'full_day' => $vendor->full_day_price,
            'hourly' => $vendor->hourly_price,
            'per_video' => $vendor->per_video_price,
        ];

        if ($packageType && array_key_exists($packageType, $map) && $map[$packageType] !== null) {
            return (float) $map[$packageType];
        }

        $prices = array_filter([
            $vendor->half_day_price,
            $vendor->full_day_price,
            $vendor->hourly_price,
            $vendor->per_video_price,
        ], fn ($value) => $value !== null);

        return $prices === [] ? null : (float) min($prices);
    }

    protected function isAvailable(Vendor $vendor, SearchQuery $query): bool
    {
        $slots = $vendor->availabilities->where('status', 'open');

        if ($slots->isEmpty()) {
            return false;
        }

        if ($query->availableOn) {
            $day = Carbon::parse($query->availableOn)->toDateString();

            return $slots->contains(fn ($slot): bool => $slot->starts_at?->toDateString() === $day
                || ($slot->starts_at && $slot->ends_at && $slot->starts_at->toDateString() <= $day && $slot->ends_at->toDateString() >= $day));
        }

        if ($query->availability === 'today') {
            $day = now()->toDateString();

            return $slots->contains(fn ($slot): bool => $slot->starts_at?->toDateString() === $day);
        }

        if ($query->availability === 'weekend') {
            $saturday = now()->next('Saturday')->toDateString();
            $sunday = now()->next('Sunday')->toDateString();

            return $slots->contains(function ($slot) use ($saturday, $sunday): bool {
                $day = $slot->starts_at?->toDateString();

                return in_array($day, [$saturday, $sunday], true);
            });
        }

        return $slots->isNotEmpty();
    }

    protected function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earth = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earth * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
