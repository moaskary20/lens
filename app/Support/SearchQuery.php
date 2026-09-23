<?php

namespace App\Support;

class SearchQuery
{
    /**
     * @param  list<string>  $categorySlugs
     * @param  list<string>  $vendorTypeSlugs
     * @param  list<string>  $filterTagSlugs
     */
    public function __construct(
        public ?string $brief = null,
        public array $categorySlugs = [],
        public array $vendorTypeSlugs = [],
        public array $filterTagSlugs = [],
        public ?int $cityId = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
        public ?float $radiusKm = null,
        public ?string $availableOn = null,
        public ?string $availability = null,
        public ?float $minPrice = null,
        public ?float $maxPrice = null,
        public ?string $packageType = null,
        public bool $verifiedOnly = false,
        public bool $topRated = false,
        public int $limit = 20,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            brief: $data['brief'] ?? $data['q'] ?? null,
            categorySlugs: array_values(array_filter((array) ($data['category_slugs'] ?? []))),
            vendorTypeSlugs: array_values(array_filter((array) ($data['vendor_type_slugs'] ?? []))),
            filterTagSlugs: array_values(array_filter((array) ($data['filter_tag_slugs'] ?? []))),
            cityId: isset($data['city_id']) ? (int) $data['city_id'] : null,
            latitude: isset($data['latitude']) ? (float) $data['latitude'] : null,
            longitude: isset($data['longitude']) ? (float) $data['longitude'] : null,
            radiusKm: isset($data['radius_km']) ? (float) $data['radius_km'] : null,
            availableOn: $data['available_on'] ?? null,
            availability: $data['availability'] ?? null,
            minPrice: isset($data['min_price']) ? (float) $data['min_price'] : null,
            maxPrice: isset($data['max_price']) ? (float) $data['max_price'] : null,
            packageType: $data['package_type'] ?? null,
            verifiedOnly: (bool) ($data['verified'] ?? false),
            topRated: (bool) ($data['top_rated'] ?? false),
            limit: max(1, (int) ($data['limit'] ?? 20)),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'brief' => $this->brief,
            'category_slugs' => $this->categorySlugs,
            'vendor_type_slugs' => $this->vendorTypeSlugs,
            'filter_tag_slugs' => $this->filterTagSlugs,
            'city_id' => $this->cityId,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'radius_km' => $this->radiusKm,
            'available_on' => $this->availableOn,
            'availability' => $this->availability,
            'min_price' => $this->minPrice,
            'max_price' => $this->maxPrice,
            'package_type' => $this->packageType,
            'verified' => $this->verifiedOnly,
            'top_rated' => $this->topRated,
        ];
    }
}
