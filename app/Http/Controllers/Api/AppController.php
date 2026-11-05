<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppScreen;
use App\Models\Category;
use App\Models\City;
use App\Models\Setting;
use App\Models\Vendor;
use App\Models\VendorType;
use App\Services\VendorSearch;
use App\Support\AppClient;
use App\Support\AppUpdate;
use App\Support\Egypt;
use App\Support\Feature;
use App\Support\Roles;
use App\Support\SearchEngine;
use App\Support\VendorAppProfile;
use App\Support\VendorProfile;
use App\Support\VendorRegisterCatalog;
use App\Support\SearchQuery;
use App\Support\VendorPhotos;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AppController extends Controller
{
    public function __construct(protected VendorSearch $search) {}

    public function bootstrap(): JsonResponse
    {
        $search = SearchEngine::settings();
        $client = AppClient::user();
        $features = Roles::appFeatures($client);
        $features['ai_assistant'] = ($features['ai_assistant'] ?? false) && (bool) ($search['ai_enabled'] ?? false);
        $logoPath = Setting::getValue('platform.logo');
        $logoUrl = is_string($logoPath) ? $this->publicUrl($logoPath) : null;

        $types = VendorType::query()->marketplace()->orderBy('sort_order')->orderBy('name_en')->get();
        $catalog = $this->bootstrapCatalog($types);
        // Always fresh so Admin → Vendor Types → Enabled updates Categories immediately.
        $catalog['vendor_types'] = $types->map(fn (VendorType $type): array => [
            'slug' => $type->slug,
            'name' => $type->name_en,
            'label' => $this->typeLabel($type->slug, $type->name_en),
            'icon' => $type->icon,
            'name_ar' => $type->name_ar,
            'sort_order' => (int) $type->sort_order,
        ])->values()->all();

        return response()->json(array_merge($catalog, [
            'name' => Setting::getValue('platform.app_name', 'Lens'),
            'logo' => $logoUrl,
            'tagline' => 'Find. Book. Create.',
            'locale' => 'en',
            'currency' => 'EGP',
            'search_placeholder' => 'Search photographers, studios, models...',
            'ai_prompt' => $search['ai_prompt'] ?? 'What will you create today?',
            'ai_helper' => 'Describe your idea and let AI find the right creatives for you.',
            'voice_input_enabled' => (bool) ($search['voice_input_enabled'] ?? true),
            'moodboard_mode' => $search['moodboard_mode'] ?? 'after_payment',
            'app_update' => AppUpdate::forBootstrap(),
            'unread_notifications' => ($features['notifications'] ?? false) ? (int) ($client?->unreadNotifications()->count() ?? 0) : 0,
            'favorite_ids' => ($features['favorites'] ?? false) && $client
                ? $client->favorites()->pluck('vendor_id')->map(fn ($id) => (int) $id)->values()->all()
                : [],
            'capabilities' => $client ? Roles::capabilitiesFor($client->role) : [],
            'colors' => [
                'primary' => '#FF5A1F',
                'primary_deep' => '#B73A0F',
                'warning' => '#F79646',
                'success' => '#3D8B5F',
                'charcoal' => '#0D0D0F',
                'cream' => '#F2EFE9',
            ],
            'features' => $features,
        ]));
    }

    /**
     * Shared marketplace catalog used by home Categories / popular rails.
     * Uses the file cache store — database cache on slow disks made cold
     * bootstrap take many seconds of cache INSERTs.
     *
     * @param  \Illuminate\Support\Collection<int, VendorType>  $types
     * @return array<string, mixed>
     */
    protected function bootstrapCatalog($types): array
    {
        $version = (string) $types->pluck('id')->sort()->implode('-');

        return Cache::store('file')->remember('lens.app.bootstrap.catalog.v7.'.$version, now()->addMinutes(5), function () use ($types): array {
            $categories = Category::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name_en')
                ->get(['slug', 'name_en', 'name_ar', 'icon', 'sort_order']);

            return [
                'categories' => $categories->map(fn (Category $category): array => [
                    'slug' => $category->slug,
                    'name' => $category->name_en,
                    'label' => $category->name_en,
                    'name_ar' => $category->name_ar,
                    'icon' => $category->icon,
                    'sort_order' => (int) $category->sort_order,
                ])->values()->all(),
                'popular' => $this->popular($types),
                'filter_catalog' => SearchEngine::appFilters(),
                'cities' => City::query()
                    ->where('is_active', true)
                    ->whereHas('vendors', fn ($query) => $query->marketplaceVisible())
                    ->orderBy('name_en')
                    ->get(['id', 'name_en', 'name_ar'])
                    ->map(fn (City $city): array => [
                        'id' => $city->id,
                        'name' => $city->name_en,
                        'name_en' => $city->name_en,
                        'name_ar' => $city->name_ar,
                    ])->values()->all(),
                'banks' => collect(Egypt::banks())
                    ->map(fn (string $label, string $id): array => ['id' => $id, 'label' => $label])
                    ->values()->all(),
                'telecom_wallets' => collect(Egypt::telecomWallets())
                    ->map(fn (string $label, string $id): array => ['id' => $id, 'label' => $label])
                    ->values()->all(),
                'vendor_register' => VendorRegisterCatalog::types(),
                'screens' => AppScreen::query()
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->get(['slug', 'title', 'body', 'cta_label', 'image', 'sort_order']),
            ];
        });
    }

    public function vendors(Request $request): JsonResponse
    {
        $slug = trim((string) $request->query('vendor_type', ''));
        $filterTags = array_values(array_filter(array_map(
            static fn ($item): string => trim((string) $item),
            explode(',', (string) $request->query('filter_tags', '')),
        )));
        $results = $this->search->search(SearchQuery::fromArray([
            'brief' => $request->query('q', $request->query('brief')),
            'vendor_type_slugs' => $slug === '' ? [] : [$slug],
            'filter_tag_slugs' => $filterTags,
            'city_id' => $request->query('city_id'),
            'verified' => $request->boolean('verified'),
            'top_rated' => $request->boolean('top_rated'),
            'min_price' => $request->query('min_price'),
            'max_price' => $request->query('max_price'),
            'limit' => max(1, min(50, (int) $request->query('limit', 40))),
        ]));

        $type = $slug === '' ? null : VendorType::query()->where('slug', $slug)->first();

        return response()->json([
            'slug' => $slug,
            'title' => $type ? $this->typeLabel($type->slug, $type->name_en) : 'Creatives',
            'total' => $results['total'],
            'vendors' => array_map(fn (array $row): array => $this->card($row), $results['vendors']),
        ]);
    }

    public function show(Vendor $vendor): JsonResponse
    {
        return response()->json($this->publicProfile($vendor));
    }

    public function vendorAvailability(Request $request, Vendor $vendor): JsonResponse
    {
        abort_unless($vendor->isMarketplaceVisible(), 404);
        abort_unless(Feature::enabled('bookings'), 404);

        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $query = $vendor->availabilities()
            ->where('status', 'open')
            ->orderBy('starts_at');

        if (! empty($data['from'])) {
            $query->where('ends_at', '>=', $data['from']);
        }
        if (! empty($data['to'])) {
            $query->where('starts_at', '<=', $data['to']);
        } else {
            $query->where('starts_at', '>=', now()->subDay());
        }

        $slots = $query->limit(120)->get()->map(fn ($slot): array => [
            'id' => $slot->id,
            'starts_at' => $slot->starts_at?->toIso8601String(),
            'ends_at' => $slot->ends_at?->toIso8601String(),
            'status' => $slot->status,
        ])->values()->all();

        return response()->json([
            'vendor_id' => $vendor->id,
            'availabilities' => $slots,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function publicProfile(Vendor $vendor, bool $forApp = true): array
    {
        abort_unless($vendor->isMarketplaceVisible(), 404);

        $vendor->loadMissing(['portfolios', 'city', 'badges', 'categories', 'vendorType', 'reviews.client', 'user']);
        $slug = $vendor->vendorType?->slug;
        $payload = $this->card(['vendor' => $vendor, 'score' => 0, 'matched_tags' => [], 'reasons' => []]);
        $filters = VendorPhotos::filters($slug);
        $categories = array_values(array_filter($filters, fn (string $item): bool => $item !== 'All'));

        $projects = VendorAppProfile::presentProjects($vendor);
        $portfolio = [];
        foreach (array_values($projects) as $index => $project) {
            $cover = $project['cover_url'] ?? null;
            if (! $cover && ! empty($project['files'][0]['url'])) {
                $cover = $project['files'][0]['url'];
            }
            // Seeded / incomplete projects may have titles without uploaded files yet.
            $cover = $cover ?: VendorPhotos::cover($slug, $index);
            $portfolio[] = [
                'id' => $project['id'] ?? null,
                'url' => $cover,
                'title' => $project['title'] ?: 'Featured work',
                'category' => $categories[$index % max(1, count($categories))] ?? 'All',
                'tall' => $index % 3 === 0,
                'media_count' => (int) ($project['media_count'] ?? count($project['files'] ?? [])),
                'photo_count' => (int) ($project['photo_count'] ?? 0),
                'video_count' => (int) ($project['video_count'] ?? 0),
            ];
            $projects[$index]['cover_url'] = $projects[$index]['cover_url'] ?: $cover;
        }

        // Portfolio / equipment come only from admin/vendor data — no invented fillers.
        $equipmentDetails = VendorProfile::presentFreeTextGear($vendor);
        $equipment = array_values(array_filter(array_map(
            fn (array $item): string => trim(($item['label'] ?? '').': '.($item['value'] ?? ''), ': '),
            $equipmentDetails,
        )));
        if ($equipment === []) {
            $equipment = array_values(array_filter($vendor->equipment ?? []));
        }

        $packages = $vendor->bookablePackages();
        $projectTypes = $vendor->categories
            ->sortBy('sort_order')
            ->values()
            ->map(fn ($category): array => [
                'slug' => $category->slug,
                'label' => $category->name_en,
            ])
            ->all();
        if ($projectTypes === []) {
            $projectTypes = Category::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get(['slug', 'name_en'])
                ->map(fn (Category $category): array => [
                    'slug' => $category->slug,
                    'label' => $category->name_en,
                ])
                ->all();
        }
        $client = AppClient::user();
        $contactUnlocked = $forApp && $client && $vendor->bookings()
            ->where('client_id', $client->id)
            ->whereNotIn('status', ['cancelled', 'rejected', 'failed'])
            ->exists();
        $contactPhone = $vendor->contact_phone ?: $vendor->user?->phone;
        $location = $payload['location'] ?: 'Cairo, Egypt';
        $bio = trim((string) $vendor->bio);
        if ($bio === '') {
            $tags = $payload['tags'] === [] ? 'campaigns' : implode(', ', $payload['tags']);
            $bio = ($payload['display_name'] ?? 'This creative').' is a '.strtolower((string) ($vendor->vendorType?->name_en ?: 'creator')).' based in '.$location.', creating booked work across '.$tags.'.';
        }

        return array_merge($payload, [
            'bio' => $bio,
            'profession' => $vendor->profession ?: $vendor->vendorType?->name_en,
            'tagline' => match ($slug) {
                'food_stylist' => "Good Food\nBetter Stories",
                'photographer' => "Light.\nThen Story.",
                'videographer', 'reels' => "Frame the\nMoment.",
                'studio' => "Space to\nCreate.",
                default => "Find. Book.\nCreate.",
            },
            'response_minutes' => (int) ($vendor->response_minutes ?: 45),
            'completed_sessions' => (int) ($vendor->completed_sessions ?? 0),
            'assisted_sessions' => (int) ($vendor->assisted_sessions ?? 0),
            'years_experience' => (int) ($vendor->years_experience ?? 0),
            'client_satisfaction' => (int) ($vendor->client_satisfaction ?? 0),
            'available_weekend' => true,
            'equipment' => $equipment,
            'equipment_details' => $equipmentDetails,
            'specialties' => $payload['tags'] ?: array_slice($categories, 0, 3),
            'project_types' => $projectTypes,
            'packages' => $packages,
            'portfolio' => $portfolio,
            'projects' => $projects,
            'portfolio_filters' => $filters,
            'reviews' => $vendor->reviews
                ->where('is_visible', true)
                ->sortByDesc('created_at')
                ->take(8)
                ->values()
                ->map(fn ($review): array => [
                    'rating' => (int) $review->rating,
                    'comment' => $review->comment,
                    'client' => $review->client?->name ?: 'Client',
                ])
                ->all(),
            'social' => [
                [
                    'label' => 'Instagram',
                    'url' => $vendor->instagram,
                    'followers' => $vendor->instagram_followers !== null ? (int) $vendor->instagram_followers : null,
                ],
                [
                    'label' => 'TikTok',
                    'url' => $vendor->tiktok_url,
                    'followers' => $vendor->tiktok_followers !== null ? (int) $vendor->tiktok_followers : null,
                ],
                [
                    'label' => 'Facebook',
                    'url' => $vendor->facebook_url,
                    'followers' => $vendor->facebook_followers !== null ? (int) $vendor->facebook_followers : null,
                ],
            ],
            'contact_unlocked' => $contactUnlocked,
            'contact_phone' => $contactUnlocked ? $contactPhone : null,
            'whatsapp' => $contactUnlocked ? ($vendor->whatsapp ?: $contactPhone) : null,
        ]);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, VendorType>  $types
     * @return list<array<string, mixed>>
     */
    protected function popular($types): array
    {
        // No nested cache — bootstrapCatalog already memoizes this payload.
        $sections = [];

        foreach ($types as $type) {
            $vendors = Vendor::query()
                ->marketplaceVisible()
                ->where('vendor_type_id', $type->id)
                ->with(['vendorType', 'city', 'badges', 'portfolios', 'categories', 'filterTags'])
                ->orderByDesc('is_featured')
                ->orderByDesc('rating_avg')
                ->orderByDesc('rating_count')
                ->limit(8)
                ->get();

            if ($vendors->isEmpty()) {
                continue;
            }

            $sections[] = [
                'slug' => $type->slug,
                'title' => $this->popularTitle($type->slug, $type->name_en),
                'vendors' => $vendors
                    ->map(fn (Vendor $vendor): array => $this->card([
                        'vendor' => $vendor,
                        'score' => 0,
                        'matched_tags' => [],
                        'reasons' => [],
                    ]))
                    ->values()
                    ->all(),
            ];
        }

        return $sections;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    protected function card(array $row): array
    {
        $presented = $this->search->present($row);
        /** @var Vendor $vendor */
        $vendor = $row['vendor'];
        $vendor->loadMissing(['portfolios', 'city', 'badges', 'categories', 'filterTags']);

        $cover = $vendor->cover_image
            ?? $vendor->portfolios->firstWhere('is_featured', true)?->path
            ?? $vendor->portfolios->sortBy('sort_order')->first()?->path;

        $slug = $vendor->vendorType?->slug;
        $prices = array_values(array_filter([
            $vendor->half_day_price,
            $vendor->hourly_price,
            $vendor->per_video_price,
            $vendor->full_day_price,
        ], fn ($price) => $price !== null && (float) $price > 0));
        $starting = $prices === [] ? null : min(array_map(fn ($price) => (float) $price, $prices));

        $tags = $vendor->categories
            ->take(3)
            ->map(fn ($category) => $this->tagLabel($category->slug, $category->name_en))
            ->values()
            ->all();

        if ($slug === 'model') {
            $modelTags = $vendor->filterTags
                ->filter(fn ($tag) => in_array($tag->group_key, ['model_category', 'model_experience'], true))
                ->pluck('name_en')
                ->filter()
                ->values()
                ->all();
            if ($modelTags !== []) {
                $tags = array_slice($modelTags, 0, 3);
            }
        }

        if ($slug === 'food_stylist') {
            $foodTags = $vendor->filterTags
                ->filter(fn ($tag) => in_array($tag->group_key, ['food_expertise', 'food_cuisine', 'food_content'], true))
                ->reject(fn ($tag) => str_ends_with((string) $tag->slug, '-all'))
                ->pluck('name_en')
                ->filter()
                ->values()
                ->all();
            if ($foodTags !== []) {
                $tags = array_slice($foodTags, 0, 3);
            }
        }

        if ($slug === 'ugc') {
            $ugcTags = $vendor->filterTags
                ->filter(fn ($tag) => in_array($tag->group_key, ['ugc_niche', 'ugc_content'], true))
                ->reject(fn ($tag) => str_ends_with((string) $tag->slug, '-all'))
                ->pluck('name_en')
                ->filter()
                ->values()
                ->all();
            if ($ugcTags !== []) {
                $tags = array_slice($ugcTags, 0, 3);
            }
        }

        if ($slug === 'studio') {
            $studioTags = $vendor->filterTags
                ->filter(fn ($tag) => in_array($tag->group_key, ['studio_type', 'studio_features'], true))
                ->reject(fn ($tag) => in_array($tag->slug, ['studio-type-all', 'studio-equipment-all'], true))
                ->pluck('name_en')
                ->filter()
                ->values()
                ->all();
            if ($studioTags !== []) {
                $tags = array_slice($studioTags, 0, 3);
            }
        }

        if ($tags === [] && is_array($vendor->specialties)) {
            $tags = collect($vendor->specialties)
                ->take(3)
                ->map(fn ($item) => $this->tagLabel((string) $item, ucfirst((string) $item)))
                ->all();
        }

        return array_merge($presented, [
            'profile_photo_url' => $this->publicUrl($vendor->profile_photo) ?: VendorPhotos::portrait($slug, (int) $vendor->id),
            'cover_url' => $this->publicUrl($cover) ?: VendorPhotos::cover($slug, (int) $vendor->id),
            'location' => trim(($vendor->city?->name_en ?? '').', Egypt', ' ,'),
            'initials' => $this->initials($vendor->display_name),
            'tags' => $tags,
            'filter_tags' => $vendor->filterTags->pluck('slug')->values()->all(),
            'starting_from' => $starting,
            'verified' => $vendor->verification_status === 'verified' || $vendor->badges->contains('slug', 'verified'),
            'completed_sessions' => (int) ($vendor->completed_sessions ?? 0),
            'assisted_sessions' => (int) ($vendor->assisted_sessions ?? 0),
            'years_experience' => (int) ($vendor->years_experience ?? 0),
            'client_satisfaction' => (int) ($vendor->client_satisfaction ?? 0),
            'latitude' => $this->coord($vendor->latitude ?? $vendor->city?->latitude, (int) $vendor->id, 30.0444, 0.04),
            'longitude' => $this->coord($vendor->longitude ?? $vendor->city?->longitude, (int) $vendor->id + 5, 31.2357, 0.05),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function publicCard(Vendor $vendor): array
    {
        return $this->card(['vendor' => $vendor, 'score' => 0, 'matched_tags' => [], 'reasons' => []]);
    }

    protected function coord(mixed $value, int $seed, float $fallback, float $spread): float
    {
        $start = ($value === null || $value === '') ? $fallback : (float) $value;

        return round($start + ((($seed % 9) - 4) * ($spread / 4)), 6);
    }

    protected function publicUrl(?string $path): ?string
    {
        if (! filled($path)) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return asset('storage/'.$path);
    }

    protected function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $letters = collect($parts)->filter()->take(2)->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)));

        return $letters->implode('') ?: 'L';
    }

    protected function tagLabel(string $slug, string $fallback): string
    {
        return match ($slug) {
            'fnb' => 'F&B',
            'wedding' => 'Weddings',
            'events' => 'Events',
            'product' => 'Product',
            'photosession' => 'Portrait',
            'corporate' => 'Corporate',
            'editorial' => 'Editorial',
            'lifestyle' => 'Lifestyle',
            'fashion' => 'Fashion',
            default => $fallback,
        };
    }

    protected function typeLabel(string $slug, string $fallback): string
    {
        return match ($slug) {
            'photographer' => 'Photographers',
            'videographer' => 'Videographers',
            'reels' => 'Mobile Reels',
            'studio' => 'Studios',
            'model' => 'Models',
            'ugc' => 'UGC Creators',
            'food_stylist' => 'Food Stylists',
            'ai_motion' => 'AI Videos & Motion',
            'bloggers' => 'Bloggers & Influencers',
            'voice' => 'Voice Over',
            default => $fallback,
        };
    }

    protected function popularTitle(string $slug, string $name): string
    {
        return match ($slug) {
            'studio' => 'Featured Studios',
            'photographer' => 'Popular Photographer',
            'videographer' => 'Popular Videographer',
            default => 'Popular '.$name,
        };
    }
}
