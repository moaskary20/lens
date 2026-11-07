<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Api\AppController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\IssueController;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\IssueReport;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use App\Models\Vendor;
use App\Services\AiAssistant;
use App\Services\VendorSearch;
use App\Support\SearchQuery;
use App\Support\SiteAuth;
use App\Support\SitePages;
use App\Support\VendorNamePrivacy;
use App\Support\VendorPhotos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(
        protected AppController $app,
        protected VendorSearch $search,
        protected AiAssistant $assistant,
    ) {}

    public function home(): View
    {
        if (config('lens.coming_soon')) {
            return $this->comingSoon();
        }

        return $this->marketplace();
    }

    public function comingSoon(): View
    {
        return view('site.coming-soon', [
            'launchAt' => (string) config('lens.launch_at'),
        ]);
    }

    public function marketplace(): View
    {
        $data = $this->bootstrap();

        return view('site.home', [
            'data' => $data,
            'slides' => $this->slides(),
            'active' => 'home',
        ]);
    }

    public function search(Request $request): View
    {
        $data = $this->bootstrap();
        $q = trim((string) $request->query('q', $request->query('brief', '')));
        $slug = trim((string) $request->query('vendor_type', ''));
        $ai = $request->boolean('ai');
        $sort = (string) $request->query('sort', 'recommended');
        $view = $request->query('view') === 'map' ? 'map' : 'list';
        $tagSlugs = collect($request->input('tags', []))->flatten()->filter()->map(fn ($item) => (string) $item)->values()->all();
        $cityId = $request->filled('city_id') ? $request->integer('city_id') : null;
        $minPrice = $request->filled('min_price') ? (float) $request->query('min_price') : null;
        $maxPrice = $request->filled('max_price') ? (float) $request->query('max_price') : null;
        $minRating = $request->filled('min_rating') ? (float) $request->query('min_rating') : null;
        $verified = $request->boolean('verified');
        $title = 'Search';
        $summary = null;
        $vendors = [];

        if ($slug !== '') {
            $match = collect($data['vendor_types'] ?? [])->firstWhere('slug', $slug);
            $title = $match['label'] ?? 'Creatives';
        }

        if ($ai && $q !== '' && $this->assistant->enabled()) {
            $result = $this->assistant->assist($q);
            $summary = $result['summary'] ?? null;
            $vendors = array_map(
                fn (array $row) => $this->app->publicCard($row['vendor']),
                $result['results']['vendors'] ?? [],
            );
        } else {
            $found = $this->search->search(SearchQuery::fromArray([
                'brief' => $q,
                'vendor_type_slugs' => $slug === '' ? [] : [$slug],
                'filter_tag_slugs' => $tagSlugs,
                'city_id' => $cityId,
                'min_price' => $minPrice,
                'max_price' => $maxPrice,
                'verified' => $verified,
                'limit' => 40,
            ]));
            $vendors = array_map(fn (array $row) => $this->app->publicCard($row['vendor']), $found['vendors']);
        }

        $vendors = VendorNamePrivacy::presentMany($vendors, $this->webUser());
        $vendors = collect($vendors);
        if ($minRating) {
            $vendors = $vendors->filter(fn (array $vendor): bool => (float) ($vendor['rating_avg'] ?? 0) >= $minRating);
        }
        $vendors = $this->sortVendors($vendors, $sort)->values()->all();

        $filterGroups = collect($data['filter_catalog'] ?? [])
            ->filter(function (array $group) use ($slug): bool {
                $options = $group['options'] ?? [];
                if ($options === []) {
                    return false;
                }
                if ($slug === '') {
                    return in_array($group['slug'] ?? '', ['availability', 'camera', 'studio_type', 'ugc_niche'], true);
                }
                $types = $group['vendor_type_slugs'] ?? [];

                return $types === [] || in_array($slug, $types, true);
            })
            ->map(function (array $group): array {
                $group['options'] = array_slice($group['options'] ?? [], 0, 16);

                return $group;
            })
            ->values()
            ->all();

        return view('site.search', [
            'data' => $data,
            'active' => $slug !== '' ? 'explore' : 'search',
            'q' => $q,
            'ai' => $ai,
            'slug' => $slug,
            'title' => $title,
            'summary' => $summary,
            'vendors' => $vendors,
            'sort' => $sort,
            'view' => $view,
            'tagSlugs' => $tagSlugs,
            'cityId' => $cityId,
            'minPrice' => $minPrice,
            'maxPrice' => $maxPrice,
            'minRating' => $minRating,
            'verified' => $verified,
            'filterGroups' => $filterGroups,
            'mapVendors' => collect($vendors)->map(fn (array $vendor): array => [
                'id' => $vendor['id'],
                'name' => $vendor['display_name'],
                'lat' => $vendor['latitude'] ?? 30.0444,
                'lng' => $vendor['longitude'] ?? 31.2357,
                'url' => route('site.vendor', $vendor['id']),
            ])->values()->all(),
        ]);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $vendors
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    protected function sortVendors($vendors, string $sort)
    {
        return match ($sort) {
            'rating' => $vendors->sortByDesc(fn (array $vendor): float => (float) ($vendor['rating_avg'] ?? 0)),
            'reviews' => $vendors->sortByDesc(fn (array $vendor): int => (int) ($vendor['rating_count'] ?? 0)),
            'price_asc' => $vendors->sortBy(fn (array $vendor): float => (float) ($vendor['starting_from'] ?? 9_999_999)),
            'price_desc' => $vendors->sortByDesc(fn (array $vendor): float => (float) ($vendor['starting_from'] ?? 0)),
            'location' => $vendors->sortBy(fn (array $vendor): string => (string) ($vendor['city'] ?? '')),
            default => $vendors,
        };
    }

    public function bookings(Request $request): View
    {
        $user = $this->webUser();
        $tab = in_array($request->query('tab'), ['upcoming', 'completed', 'canceled'], true)
            ? (string) $request->query('tab')
            : 'upcoming';
        $bookings = [];
        $summary = ['upcoming' => 0, 'completed' => 0, 'canceled' => 0];
        $role = 'guest';

        if ($user) {
            $auth = app(AuthController::class);
            $query = Booking::query()->with(['vendor.vendorType', 'vendor.city', 'vendor.badges', 'vendor.user', 'client', 'category', 'dispute']);
            if ($user->isVendor() && $user->vendor) {
                $query->where('vendor_id', $user->vendor->id);
                $role = 'vendor';
            } else {
                $query->where('client_id', $user->id);
                $role = 'client';
            }

            $items = $query->orderByDesc('scheduled_at')->limit(80)->get()
                ->map(fn (Booking $booking): array => $auth->presentBooking($booking, $role));

            foreach ($items as $item) {
                $group = (string) ($item['group'] ?? 'upcoming');
                if (! array_key_exists($group, $summary)) {
                    $group = 'upcoming';
                }
                $summary[$group]++;
            }

            $bookings = $items
                ->filter(fn (array $item): bool => ($item['group'] ?? 'upcoming') === $tab)
                ->values()
                ->all();
        }

        return view('site.bookings', [
            'data' => $this->bootstrap(),
            'active' => 'bookings',
            'user' => $user,
            'role' => $role,
            'tab' => $tab,
            'summary' => $summary,
            'bookings' => $bookings,
        ]);
    }

    public function bookingsLogin(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()->where('email', $data['email'])->first();
        if (! $user || ! Hash::check($data['password'], $user->password) || ! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => 'Invalid email or password.',
            ]);
        }

        if (! $user->canUseClientApp() && ! $user->isVendor()) {
            throw ValidationException::withMessages([
                'email' => 'This account cannot open bookings.',
            ]);
        }

        SiteAuth::login($user, $request);

        return redirect()->route('site.bookings');
    }

    public function bookingsLogout(Request $request): RedirectResponse
    {
        SiteAuth::logout($request);

        return redirect()->route('site.bookings');
    }

    public function explore(): View
    {
        $data = $this->bootstrap();

        return view('site.explore', [
            'data' => $data,
            'active' => 'explore',
            'types' => $data['vendor_types'] ?? [],
            'popular' => $data['popular'] ?? [],
        ]);
    }

    protected function webUser(): ?User
    {
        return SiteAuth::user();
    }

    public function help(): View
    {
        $user = SiteAuth::user();

        return view('site.help', [
            'data' => $this->bootstrap(),
            'active' => 'home',
            'user' => $user,
            'topics' => IssueReport::TOPICS,
            'guides' => [
                [
                    'key' => 'booking',
                    'title' => 'Bookings',
                    'body' => 'Search a category, open a creator profile, pick a package and date, then pay into escrow. Contact details unlock after the booking is confirmed.',
                ],
                [
                    'key' => 'payment',
                    'title' => 'Payments & escrow',
                    'body' => 'Funds stay protected until you approve delivery. Lens staff can review disputes, no-shows, and refunds from the booking desk.',
                ],
                [
                    'key' => 'account',
                    'title' => 'Account & profile',
                    'body' => 'Update your photo, phone, addresses, and notification settings from Account. Creators manage portfolio, pricing, and availability in the vendor console.',
                ],
                [
                    'key' => 'app',
                    'title' => 'App & chat',
                    'body' => 'Use the Lens app for chat, call, and WhatsApp on shoot day. Push alerts cover bookings, messages, and review prompts.',
                ],
                [
                    'key' => 'creator',
                    'title' => 'Creators',
                    'body' => 'Verified creators publish packages, portfolio, and calendar. Keep response times fast and delivery clear so clients can release funds confidently.',
                ],
                [
                    'key' => 'other',
                    'title' => 'What is Lens?',
                    'body' => 'Lens helps you find, book, and chat with verified creators in Egypt. Payments stay in escrow until you approve the work.',
                ],
            ],
        ]);
    }

    public function helpReport(Request $request): RedirectResponse
    {
        $user = SiteAuth::user();
        if ($user) {
            SiteAuth::applyClientHeader($request);
            $request->merge([
                'name' => $request->input('name') ?: $user->name,
                'email' => $request->input('email') ?: $user->email,
                'phone' => $request->input('phone') ?: $user->phone,
                'platform' => 'web',
            ]);
        } else {
            $request->merge([
                'guest' => true,
                'platform' => 'web',
            ]);
        }

        $response = app(IssueController::class)->store($request);
        $payload = $response->getData(true);
        if ($response->getStatusCode() >= 400) {
            return back()
                ->withErrors($payload['errors'] ?? ['form' => $payload['message'] ?? 'Could not send report.'])
                ->withInput();
        }

        return redirect()
            ->route('site.help')
            ->with('help_report_ok', $payload['reference'] ?? true)
            ->with('status', $payload['message'] ?? 'Your report is with Lens support.');
    }

    public function page(string $slug): View|RedirectResponse
    {
        if ($slug === 'help') {
            return redirect()->route('site.help');
        }

        $page = SitePages::resolve($slug);

        return view('site.page', array_merge($page, [
            'data' => $this->bootstrap(),
            'active' => 'home',
        ]));
    }

    public function newsletter(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:255']]);
        NewsletterSubscriber::query()->firstOrCreate(
            ['email' => mb_strtolower(trim($data['email']))],
            ['source' => 'site_footer'],
        );

        return back()->with('newsletter_ok', true);
    }

    public function vendor(Vendor $vendor): View
    {
        abort_unless($vendor->isMarketplaceVisible(), 404);

        $profile = $this->app->publicProfile($vendor, true);
        $user = $this->webUser();
        $nameUnlocked = VendorNamePrivacy::unlocked($user, (int) $vendor->id);
        $profile = VendorNamePrivacy::present($profile, $user);
        if (! $nameUnlocked) {
            $profile['contact_unlocked'] = false;
            $profile['contact_phone'] = null;
            $profile['whatsapp'] = null;
        }

        $slug = $vendor->vendorType?->slug;
        if (($profile['portfolio'] ?? []) === []) {
            $profile['portfolio'] = array_map(
                fn (array $shot): array => $shot + ['id' => null],
                array_slice(VendorPhotos::gallery($slug, (int) $vendor->id), 0, 9),
            );
            $profile['portfolio_is_showcase'] = true;
        }

        $rating = (float) ($profile['rating_avg'] ?? 0);
        $ratingCount = (int) ($profile['rating_count'] ?? 0);
        $years = (int) ($profile['years_experience'] ?? 0);
        $satisfaction = (int) ($profile['client_satisfaction'] ?? 0);
        $completed = (int) ($profile['completed_sessions'] ?? 0);
        $assisted = (int) ($profile['assisted_sessions'] ?? 0);

        if ($years <= 0 && $completed > 0) {
            $years = max(1, (int) ceil($completed / 8));
        }
        if ($satisfaction <= 0 && $ratingCount > 0) {
            $satisfaction = (int) round(min(99, max(80, $rating * 20)));
        }

        $stats = array_values(array_filter([
            ['value' => (string) $completed, 'label' => 'Projects Completed', 'show' => $completed > 0],
            ['value' => (string) $assisted, 'label' => 'Assisted', 'show' => $assisted > 0],
            ['value' => $years > 0 ? $years.'+' : null, 'label' => 'Years Experience', 'show' => $years > 0],
            ['value' => $satisfaction > 0 ? $satisfaction.'%' : null, 'label' => 'Client Satisfaction', 'show' => $satisfaction > 0],
        ], fn (array $row): bool => $row['show']));

        return view('site.vendor', [
            'data' => $this->bootstrap(),
            'active' => 'explore',
            'vendor' => $profile,
            'stats' => $stats,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function bootstrap(): array
    {
        return VendorNamePrivacy::presentBootstrap(
            $this->app->bootstrap()->getData(true),
            $this->webUser(),
        );
    }

    /**
     * @return list<array{image: string, kicker: string, title: string, body: string}>
     */
    protected function slides(): array
    {
        $copy = [
            [
                'kicker' => 'Turn Moments Into Stories',
                'title' => 'Find the Perfect Photographer or Videographer',
                'body' => 'Discover talented creators, book in minutes, and bring your vision to life.',
            ],
            [
                'kicker' => 'Studios ready when you are',
                'title' => 'Book a space that matches the brief',
                'body' => 'From daylight lofts to cyclorama stages, find the right studio in a few taps.',
            ],
            [
                'kicker' => 'Creators for every format',
                'title' => 'Reels, UGC, and motion that convert',
                'body' => 'Pair your idea with specialists who already shoot the look you want.',
            ],
            [
                'kicker' => 'Protected from idea to delivery',
                'title' => 'Escrow keeps every booking honest',
                'body' => 'Pay securely, review the work, and release funds when the job is done.',
            ],
        ];

        $images = [
            '/branding/hero-pyramids.jpg',
            '/branding/hero-egypt-studio.jpg',
            '/branding/hero-egypt-creators.jpg',
            '/branding/categories/videographer.jpg',
        ];

        return collect($copy)->values()->map(function (array $slide, int $index) use ($images): array {
            $slide['image'] = $images[$index] ?? $images[0];

            return $slide;
        })->all();
    }
}
