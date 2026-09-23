<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppScreen;
use App\Models\Vendor;
use App\Models\VendorType;
use App\Services\VendorSearch;
use App\Support\AppClient;
use App\Support\Feature;
use App\Support\SearchEngine;
use App\Support\SearchQuery;
use App\Support\VendorPhotos;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppController extends Controller
{
    public function __construct(protected VendorSearch $search) {}

    public function bootstrap(): JsonResponse
    {
        $search = SearchEngine::settings();
        $features = Feature::flags();
        $features['ai_assistant'] = Feature::enabled('ai_assistant') && (bool) ($search['ai_enabled'] ?? false);

        $types = VendorType::query()->marketplace()->orderBy('sort_order')->get();

        $client = AppClient::user();

        return response()->json([
            'name' => 'Lens',
            'tagline' => 'Find. Book. Create.',
            'locale' => 'en',
            'currency' => 'EGP',
            'search_placeholder' => 'Search photographers, studios, models...',
            'ai_prompt' => $search['ai_prompt'] ?? 'What will you create today?',
            'ai_helper' => 'Describe your idea and let AI find the right creatives for you.',
            'unread_notifications' => Feature::enabled('notifications') ? (int) ($client?->unreadNotifications()->count() ?? 0) : 0,
            'favorite_ids' => Feature::enabled('favorites') && $client
                ? $client->favorites()->pluck('vendor_id')->map(fn ($id) => (int) $id)->values()->all()
                : [],
            'colors' => [
                'primary' => '#FF5A1F',
                'primary_deep' => '#B73A0F',
                'warning' => '#F79646',
                'success' => '#3D8B5F',
                'charcoal' => '#0D0D0F',
                'cream' => '#F2EFE9',
            ],
            'features' => $features,
            'vendor_types' => $types->map(fn (VendorType $type): array => [
                'slug' => $type->slug,
                'name' => $type->name_en,
                'label' => $this->typeLabel($type->slug, $type->name_en),
                'icon' => $type->icon,
            ])->values()->all(),
            'popular' => $this->popular($types),
            'filter_catalog' => SearchEngine::appFilters(),
            'screens' => AppScreen::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get(['slug', 'title', 'body', 'cta_label', 'image', 'sort_order']),
        ]);
    }

    public function vendors(Request $request): JsonResponse
    {
        $slug = trim((string) $request->query('vendor_type', ''));
        $results = $this->search->search(SearchQuery::fromArray([
            'vendor_type_slugs' => $slug === '' ? [] : [$slug],
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
        abort_unless($vendor->is_active, 404);

        $vendor->loadMissing(['portfolios', 'city', 'badges', 'categories', 'vendorType', 'reviews.client']);
        $slug = $vendor->vendorType?->slug;
        $payload = $this->card(['vendor' => $vendor, 'score' => 0, 'matched_tags' => [], 'reasons' => []]);
        $filters = VendorPhotos::filters($slug);
        $categories = array_values(array_filter($filters, fn (string $item): bool => $item !== 'All'));

        $portfolio = $vendor->portfolios
            ->sortBy('sort_order')
            ->values()
            ->map(function ($item, int $index) use ($slug, $categories): array {
                return [
                    'url' => $this->publicUrl($item->path) ?: VendorPhotos::cover($slug, $index),
                    'title' => $item->title ?: 'Featured work',
                    'category' => $categories[$index % max(1, count($categories))] ?? 'All',
                    'tall' => $index % 3 === 0,
                ];
            })
            ->all();

        if (count($portfolio) < 6) {
            $portfolio = array_values(array_merge($portfolio, VendorPhotos::gallery($slug, (int) $vendor->id)));
            $portfolio = array_slice($portfolio, 0, 8);
        }

        $equipment = array_values(array_filter($vendor->equipment ?? []));
        if ($equipment === []) {
            $equipment = match ($slug) {
                'food_stylist' => ['Prop library', 'Surface boards', 'Steam kit', 'Natural light'],
                'videographer', 'reels' => ['Sony FX3', 'Gimbal', 'LED panels', 'Wireless lavs'],
                'studio' => ['Cyclorama', 'Kitchen set', 'Strobes', 'Tethering'],
                default => ['Canon R5', '85mm f/1.2', 'Godox strobes', 'Color grading'],
            };
        }

        $packages = array_values(array_filter([
            $vendor->hourly_price ? ['label' => 'Hourly', 'price' => (float) $vendor->hourly_price] : null,
            $vendor->half_day_price ? ['label' => 'Half day', 'price' => (float) $vendor->half_day_price] : null,
            $vendor->full_day_price ? ['label' => 'Full day', 'price' => (float) $vendor->full_day_price] : null,
            $vendor->per_video_price ? ['label' => 'Per video', 'price' => (float) $vendor->per_video_price] : null,
        ]));

        $projects = (int) ($vendor->completed_sessions ?: max(24, (int) $vendor->rating_count));

        return response()->json(array_merge($payload, [
            'bio' => $vendor->bio,
            'profession' => $vendor->profession ?: $vendor->vendorType?->name_en,
            'tagline' => match ($slug) {
                'food_stylist' => "Good Food\nBetter Stories",
                'photographer' => "Light.\nThen Story.",
                'videographer', 'reels' => "Frame the\nMoment.",
                'studio' => "Space to\nCreate.",
                default => "Find. Book.\nCreate.",
            },
            'response_minutes' => (int) ($vendor->response_minutes ?: 45),
            'completed_sessions' => $projects,
            'assisted_sessions' => (int) round($projects * 0.7),
            'years_experience' => max(3, (int) floor($projects / 15)),
            'client_satisfaction' => min(99, (int) round(((float) $vendor->rating_avg) * 20)),
            'available_weekend' => true,
            'equipment' => $equipment,
            'specialties' => $payload['tags'] ?: array_slice($categories, 0, 3),
            'packages' => $packages,
            'portfolio' => $portfolio,
            'portfolio_filters' => $filters,
        ]));
    }

    /**
     * @param  \Illuminate\Support\Collection<int, VendorType>  $types
     * @return list<array<string, mixed>>
     */
    protected function popular($types): array
    {
        $sections = [];

        foreach ($types as $type) {
            $results = $this->search->search(SearchQuery::fromArray([
                'vendor_type_slugs' => [$type->slug],
                'limit' => 5,
            ]));

            if ($results['vendors'] === []) {
                continue;
            }

            $sections[] = [
                'slug' => $type->slug,
                'title' => $this->popularTitle($type->slug, $type->name_en),
                'vendors' => array_map(fn (array $row): array => $this->card($row), $results['vendors']),
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
