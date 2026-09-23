<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Coupon;
use App\Models\RecommendationRule;
use App\Models\RecommendationSignal;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorType;
use App\Support\Feature;
use App\Support\Finance;
use App\Support\SearchEngine;
use App\Support\SearchQuery;

class RecommendationService
{
    public function __construct(
        protected VendorSearch $search,
        protected AiAssistant $assistant,
    ) {}

    public function recordFromBooking(Booking $booking, string $source = 'booking'): void
    {
        if (! Feature::enabled('smart_recommendations') || ! $booking->client_id) {
            return;
        }

        $booking->loadMissing(['vendor.vendorType', 'category']);
        $weight = match ($booking->status) {
            'approved', 'completed' => 3.0,
            'cancelled', 'rejected', 'failed', 'refunded' => 0.4,
            default => 1.5,
        };

        RecommendationSignal::query()->create([
            'user_id' => $booking->client_id,
            'source' => $source,
            'vendor_id' => $booking->vendor_id,
            'vendor_type_id' => $booking->vendor?->vendor_type_id,
            'category_id' => $booking->category_id,
            'city_id' => $booking->city_id ?: $booking->vendor?->city_id,
            'amount' => $booking->session_price,
            'weight' => $weight,
            'meta' => [
                'reference' => $booking->reference,
                'status' => $booking->status,
                'package_type' => $booking->package_type,
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function recordFromSearch(?User $user, array $query, string $source = 'search'): void
    {
        if (! Feature::enabled('smart_recommendations') || ! $user) {
            return;
        }

        $typeSlug = $query['vendor_type_slugs'][0] ?? null;
        $categorySlug = $query['category_slugs'][0] ?? null;

        RecommendationSignal::query()->create([
            'user_id' => $user->id,
            'source' => $source,
            'vendor_type_id' => $typeSlug
                ? VendorType::query()->where('slug', $typeSlug)->value('id')
                : null,
            'category_id' => $categorySlug
                ? \App\Models\Category::query()->where('slug', $categorySlug)->value('id')
                : null,
            'city_id' => isset($query['city_id']) ? (int) $query['city_id'] : $user->city_id,
            'amount' => isset($query['max_price']) ? (float) $query['max_price'] : null,
            'weight' => $source === 'assistant' ? 0.8 : 0.5,
            'meta' => [
                'vendor_type_slugs' => $query['vendor_type_slugs'] ?? [],
                'category_slugs' => $query['category_slugs'] ?? [],
                'filter_tag_slugs' => $query['filter_tag_slugs'] ?? [],
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function forClient(User $client, int $limit = 6): array
    {
        if (! Feature::enabled('smart_recommendations')) {
            return $this->empty($client);
        }

        $profile = $this->profile($client);
        $similar = $this->similarVendors($profile, $limit);
        $nearby = $this->nearbyVendors($profile, $limit);
        $top = $this->topInSpecialty($profile, $limit);
        $budget = $this->budgetFit($profile, $limit);
        $vendorPool = collect([...$similar, ...$nearby, ...$top, ...$budget])
            ->pluck('id')
            ->merge($profile['booked_vendor_ids'])
            ->unique()
            ->all();

        return [
            'client' => [
                'id' => $client->id,
                'name' => $client->name,
                'city_id' => $profile['city_id'],
            ],
            'profile' => $profile,
            'similar_vendors' => $similar,
            'suitable_services' => $this->suitableServices($client, $profile),
            'suitable_offers' => $this->suitableOffers($client, $profile, $vendorPool),
            'nearby_vendors' => $nearby,
            'top_in_specialty' => $top,
            'budget_fit' => $budget,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function profile(User $client): array
    {
        $settings = SearchEngine::settings();
        $padding = (float) ($settings['recommend_budget_padding'] ?? 0.25);
        $typeWeights = [];
        $categoryWeights = [];
        $tagWeights = [];
        $cityVotes = [];
        $prices = [];
        $booked = [];

        $bookings = $client->bookings()
            ->with(['vendor.vendorType', 'vendor.categories', 'vendor.filterTags', 'category'])
            ->get();

        foreach ($bookings as $booking) {
            $weight = match ($booking->status) {
                'approved', 'completed' => 3.0,
                'cancelled', 'rejected', 'failed', 'refunded' => 0.4,
                default => 1.5,
            };
            $this->accumulate(
                $booking->vendor,
                $booking->category?->slug,
                $booking->vendor?->city_id ?: $booking->city_id,
                (float) $booking->session_price,
                $weight,
                $typeWeights,
                $categoryWeights,
                $tagWeights,
                $cityVotes,
                $prices,
                $booked,
            );
        }

        $signals = RecommendationSignal::query()
            ->where('user_id', $client->id)
            ->with(['vendor.vendorType', 'vendor.categories', 'vendor.filterTags', 'vendorType', 'category'])
            ->orderByDesc('id')
            ->limit(80)
            ->get();

        foreach ($signals as $signal) {
            $this->accumulate(
                $signal->vendor,
                $signal->category?->slug,
                $signal->city_id,
                $signal->amount !== null ? (float) $signal->amount : null,
                max(0.2, (float) $signal->weight),
                $typeWeights,
                $categoryWeights,
                $tagWeights,
                $cityVotes,
                $prices,
                $booked,
            );
            if ($signal->vendorType?->slug) {
                $typeWeights[$signal->vendorType->slug] = ($typeWeights[$signal->vendorType->slug] ?? 0) + (float) $signal->weight;
            }
        }

        arsort($typeWeights);
        arsort($categoryWeights);
        arsort($tagWeights);
        arsort($cityVotes);

        $avg = $prices === [] ? null : round(array_sum($prices) / count($prices), 2);
        $maxPaid = $prices === [] ? null : max($prices);

        return [
            'city_id' => $client->city_id ?: (array_key_first($cityVotes) ?: null),
            'vendor_type_slugs' => array_keys($typeWeights),
            'category_slugs' => array_keys($categoryWeights),
            'filter_tag_slugs' => array_keys($tagWeights),
            'booked_vendor_ids' => array_values(array_unique($booked)),
            'avg_budget' => $avg,
            'max_budget' => $maxPaid !== null ? round($maxPaid * (1 + $padding), 2) : ($avg !== null ? round($avg * (1 + $padding), 2) : null),
            'min_budget' => $avg !== null ? round($avg * 0.6, 2) : null,
            'booking_count' => $bookings->count(),
            'signal_count' => $signals->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $profile
     * @return list<array<string, mixed>>
     */
    public function similarVendors(array $profile, int $limit): array
    {
        $types = array_slice($profile['vendor_type_slugs'], 0, 2);
        $categories = array_slice($profile['category_slugs'], 0, 2);
        if ($types === []) {
            return [];
        }

        $rows = $this->search->search(SearchQuery::fromArray([
            'vendor_type_slugs' => $types,
            'category_slugs' => $categories,
            'limit' => $limit + count($profile['booked_vendor_ids']) + 4,
        ]));

        if ($rows['total'] === 0 && $categories !== []) {
            $rows = $this->search->search(SearchQuery::fromArray([
                'vendor_type_slugs' => $types,
                'limit' => $limit + 4,
            ]));
        }

        return $this->takeVendors($rows['vendors'], $limit, $profile['booked_vendor_ids'], 'Similar to vendors you already booked');
    }

    /**
     * @param  array<string, mixed>  $profile
     * @return list<array<string, mixed>>
     */
    public function nearbyVendors(array $profile, int $limit): array
    {
        if (! $profile['city_id']) {
            return [];
        }

        $rows = $this->search->search(SearchQuery::fromArray([
            'city_id' => $profile['city_id'],
            'limit' => $limit,
        ]));

        return $this->takeVendors($rows['vendors'], $limit, [], 'Near your governorate');
    }

    /**
     * @param  array<string, mixed>  $profile
     * @return list<array<string, mixed>>
     */
    public function topInSpecialty(array $profile, int $limit): array
    {
        $type = $profile['vendor_type_slugs'][0] ?? null;
        $query = Vendor::query()
            ->where('is_active', true)
            ->where('verification_status', 'verified')
            ->with(['vendorType', 'city', 'badges']);

        if ($type) {
            $query->whereHas('vendorType', fn ($builder) => $builder->where('slug', $type));
        }

        return $query->get()
            ->map(function (Vendor $vendor) use ($type): array {
                $score = ((int) $vendor->completed_sessions * 2)
                    + ((float) $vendor->rating_avg * max(1, (int) $vendor->rating_count))
                    + ($vendor->is_featured ? 20 : 0);

                $reasons = ['Most completed sessions in '.($vendor->vendorType?->name_en ?: $type ?: 'this specialty')];
                if ((float) $vendor->rating_avg > 0) {
                    $reasons[] = 'Client rating '.$vendor->rating_avg;
                }

                return $this->search->present([
                    'vendor' => $vendor,
                    'score' => round($score, 2),
                    'matched_tags' => [],
                    'reasons' => $reasons,
                ]);
            })
            ->sortByDesc('score')
            ->take($limit)
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $profile
     * @return list<array<string, mixed>>
     */
    public function budgetFit(array $profile, int $limit): array
    {
        $payload = [
            'limit' => $limit,
        ];
        if ($profile['vendor_type_slugs'] !== []) {
            $payload['vendor_type_slugs'] = array_slice($profile['vendor_type_slugs'], 0, 2);
        }
        if ($profile['max_budget'] !== null) {
            $payload['max_price'] = $profile['max_budget'];
        }
        if ($profile['min_budget'] !== null) {
            $payload['min_price'] = max(0, $profile['min_budget'] * 0.5);
        }

        $rows = $this->search->search(SearchQuery::fromArray($payload));
        $reason = $profile['avg_budget']
            ? 'Fits your typical budget around '.number_format((float) $profile['avg_budget'], 0).' '.Finance::currency()
            : 'Priced for a first booking';

        return $this->takeVendors($rows['vendors'], $limit, [], $reason);
    }

    /**
     * @param  array<string, mixed>  $profile
     * @return list<array<string, mixed>>
     */
    public function suitableServices(User $client, array $profile): array
    {
        $types = VendorType::query()->marketplace()->get()->keyBy('slug');
        $services = [];

        foreach ($profile['vendor_type_slugs'] as $index => $slug) {
            $type = $types->get($slug);
            if (! $type) {
                continue;
            }
            $services[] = [
                'kind' => 'vendor_type',
                'slug' => $slug,
                'name' => $type->name_en,
                'reason' => $index === 0 ? 'Your most booked specialty' : 'Also appears in your booking history',
            ];
        }

        foreach (array_slice($profile['category_slugs'], 0, 4) as $slug) {
            $services[] = [
                'kind' => 'category',
                'slug' => $slug,
                'name' => $slug,
                'reason' => 'Matches categories you book',
            ];
        }

        $brief = implode(' ', $profile['category_slugs']);
        foreach ($this->assistant->addOns($brief, SearchQuery::fromArray([
            'category_slugs' => $profile['category_slugs'],
            'filter_tag_slugs' => $profile['filter_tag_slugs'],
        ])) as $addon) {
            $services[] = [
                'kind' => 'add_on',
                'slug' => $addon['vendor_type'],
                'name' => $addon['vendor_type_name'] ?: $addon['name'],
                'reason' => $addon['message'],
            ];
        }

        $unique = [];
        foreach ($services as $service) {
            $key = $service['kind'].':'.$service['slug'];
            $unique[$key] ??= $service;
        }

        return array_values($unique);
    }

    /**
     * @param  array<string, mixed>  $profile
     * @param  list<int>  $vendorIds
     * @return list<array<string, mixed>>
     */
    public function suitableOffers(User $client, array $profile, array $vendorIds): array
    {
        if (! Feature::enabled('coupons')) {
            return [];
        }

        $typeIds = VendorType::query()
            ->whereIn('slug', $profile['vendor_type_slugs'])
            ->pluck('id')
            ->all();

        return Coupon::query()
            ->where('is_active', true)
            ->with(['assignedUsers', 'vendor', 'vendorType'])
            ->get()
            ->filter(function (Coupon $coupon) use ($client, $typeIds, $vendorIds): bool {
                $vendor = $coupon->vendor;
                if (! $vendor && $coupon->vendor_id && in_array((int) $coupon->vendor_id, $vendorIds, true)) {
                    $vendor = Vendor::query()->find($coupon->vendor_id);
                }
                if (! $vendor && $coupon->vendor_type_id && in_array((int) $coupon->vendor_type_id, $typeIds, true)) {
                    $vendor = Vendor::query()->where('vendor_type_id', $coupon->vendor_type_id)->first();
                }

                return $coupon->appliesTo($client, null, $vendor);
            })
            ->map(fn (Coupon $coupon): array => [
                'id' => $coupon->id,
                'code' => $coupon->code,
                'label' => $coupon->label,
                'campaign' => $coupon->campaign,
                'type' => $coupon->type,
                'amount' => (float) $coupon->amount,
                'reason' => $this->offerReason($coupon),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  list<int>  $excludeIds
     * @return list<array<string, mixed>>
     */
    protected function takeVendors(array $rows, int $limit, array $excludeIds, string $extraReason): array
    {
        $out = [];
        foreach ($rows as $row) {
            $card = $this->search->present($row);
            if (in_array((int) $card['id'], $excludeIds, true)) {
                continue;
            }
            $card['reasons'] = array_values(array_unique([...$card['reasons'], $extraReason]));
            $out[] = $card;
            if (count($out) >= $limit) {
                break;
            }
        }

        return $out;
    }

    /**
     * @param  array<string, float>  $typeWeights
     * @param  array<string, float>  $categoryWeights
     * @param  array<string, float>  $tagWeights
     * @param  array<int, float>  $cityVotes
     * @param  list<float>  $prices
     * @param  list<int>  $booked
     */
    protected function accumulate(
        ?Vendor $vendor,
        ?string $categorySlug,
        mixed $cityId,
        ?float $price,
        float $weight,
        array &$typeWeights,
        array &$categoryWeights,
        array &$tagWeights,
        array &$cityVotes,
        array &$prices,
        array &$booked,
    ): void {
        if ($vendor) {
            $booked[] = (int) $vendor->id;
            $vendor->loadMissing(['vendorType', 'categories', 'filterTags']);
            if ($vendor->vendorType?->slug) {
                $typeWeights[$vendor->vendorType->slug] = ($typeWeights[$vendor->vendorType->slug] ?? 0) + $weight;
            }
            foreach ($vendor->categories as $category) {
                $categoryWeights[$category->slug] = ($categoryWeights[$category->slug] ?? 0) + $weight;
            }
            foreach ($vendor->filterTags as $tag) {
                $tagWeights[$tag->slug] = ($tagWeights[$tag->slug] ?? 0) + ($weight * 0.4);
            }
        }

        if ($categorySlug) {
            $categoryWeights[$categorySlug] = ($categoryWeights[$categorySlug] ?? 0) + $weight;
        }

        if ($cityId) {
            $cityVotes[(int) $cityId] = ($cityVotes[(int) $cityId] ?? 0) + $weight;
        }

        if ($price && $price > 0) {
            $prices[] = $price;
        }
    }

    protected function offerReason(Coupon $coupon): string
    {
        return match ($coupon->campaign) {
            Coupon::CAMPAIGN_FIRST_ORDER => 'First-order offer based on your booking history',
            Coupon::CAMPAIGN_LOYAL => 'Loyal-client offer from your completed sessions',
            Coupon::CAMPAIGN_SEASONAL => 'Seasonal offer running now',
            Coupon::CAMPAIGN_VENDOR => 'Offer on a vendor in your feed',
            Coupon::CAMPAIGN_SERVICE => 'Offer on a specialty you book',
            Coupon::CAMPAIGN_USER => 'Assigned to your account',
            default => 'Matches your usage on Lens',
        };
    }

    /**
     * @return array<string, mixed>
     */
    protected function empty(User $client): array
    {
        return [
            'client' => ['id' => $client->id, 'name' => $client->name, 'city_id' => $client->city_id],
            'profile' => [
                'city_id' => $client->city_id,
                'vendor_type_slugs' => [],
                'category_slugs' => [],
                'filter_tag_slugs' => [],
                'booked_vendor_ids' => [],
                'avg_budget' => null,
                'max_budget' => null,
                'min_budget' => null,
                'booking_count' => 0,
                'signal_count' => 0,
            ],
            'similar_vendors' => [],
            'suitable_services' => [],
            'suitable_offers' => [],
            'nearby_vendors' => [],
            'top_in_specialty' => [],
            'budget_fit' => [],
        ];
    }
}
