@extends('site.layout', ['title' => $title, 'active' => $active ?? 'search'])

@php
    $sortLabels = [
        'recommended' => 'Recommended',
        'rating' => 'Top rated',
        'reviews' => 'Best review',
        'location' => 'Location',
        'price_asc' => 'Price: low',
        'price_desc' => 'Price: high',
    ];
    $params = collect(request()->query())->except(['sort', 'view'])->all();
    $filterOpen = $tagSlugs !== [] || $cityId || $minPrice || $maxPrice || $minRating || $verified;
    $count = count($vendors);
    $cities = collect($data['cities'] ?? []);
    $types = collect($data['vendor_types'] ?? []);
    $heroFile = ($slug !== '' && is_file(public_path('branding/categories/'.$slug.'.jpg')))
        ? asset('branding/categories/'.$slug.'.jpg')
        : asset('branding/hero-egypt-creators.jpg');
    $lede = $slug !== ''
        ? 'Browse verified '.$title.' on Lens — filter by city, price, and rating.'
        : 'Search photographers, studios, models, and more across Egypt.';
    $activeFilters = [];
    if ($cityId) {
        $cityName = $cities->firstWhere('id', (int) $cityId)['name_en']
            ?? $cities->firstWhere('id', (int) $cityId)['name']
            ?? 'City';
        $activeFilters[] = ['label' => $cityName, 'clear' => array_filter(['vendor_type' => $slug ?: null, 'q' => $q ?: null])];
    }
    if ($verified) {
        $activeFilters[] = ['label' => 'Verified', 'clear' => array_filter(array_merge($params, ['verified' => null]))];
    }
    if ($minRating) {
        $activeFilters[] = ['label' => $minRating.'+', 'clear' => array_filter(array_merge($params, ['min_rating' => null]))];
    }
    if ($minPrice || $maxPrice) {
        $priceLabel = ($minPrice ? number_format($minPrice) : '0').'–'.($maxPrice ? number_format($maxPrice) : '∞');
        $activeFilters[] = ['label' => 'EGP '.$priceLabel, 'clear' => array_filter(array_merge($params, ['min_price' => null, 'max_price' => null]))];
    }
@endphp

@section('content')
<section class="srch-hero" data-search-motion>
    <img class="srch-hero-bg" src="{{ $heroFile }}" alt="" loading="eager" decoding="async" data-srch-media>
    <div class="srch-hero-veil" aria-hidden="true"></div>
    <div class="wrap srch-hero-copy" data-srch-copy>
        <p class="srch-kicker">Lens · Search</p>
        <h1>{{ $title }}</h1>
        <p class="srch-lede">{{ $lede }}</p>

        <form class="srch-bar" action="{{ route('site.search') }}" method="get" role="search">
            @if($slug !== '')
                <input type="hidden" name="vendor_type" value="{{ $slug }}">
            @endif
            @if($cityId)
                <input type="hidden" name="city_id" value="{{ $cityId }}">
            @endif
            @foreach($tagSlugs as $tag)
                <input type="hidden" name="tags[]" value="{{ $tag }}">
            @endforeach
            <span class="srch-bar-ico" aria-hidden="true">
                <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            </span>
            <input type="search" name="q" value="{{ $q }}" placeholder="{{ $data['search_placeholder'] ?? 'Search creators, studios, models...' }}">
            <button class="ai-btn" type="submit">Search</button>
            @if(($data['features']['ai_assistant'] ?? true))
                <a
                    class="ghost-btn srch-ai"
                    href="{{ route('site.ai', array_filter(['q' => $q ?: null])) }}"
                    data-srch-ai
                >AI</a>
            @endif
        </form>
    </div>
</section>

<section class="wrap srch-shell cat-page search-page" data-category-page data-search-shell>
    <div class="srch-types" data-srch-types>
        <a class="srch-type {{ $slug === '' ? 'is-active' : '' }}" href="{{ route('site.search', array_filter(['q' => $q ?: null, 'city_id' => $cityId ?: null])) }}">
            <span class="srch-type-media" style="background-image:url('{{ asset('branding/hero-egypt-creators.jpg') }}')"></span>
            <span class="srch-type-label">All</span>
        </a>
        @foreach($types as $type)
            @php
                $typeSlug = $type['slug'] ?? '';
                $typeBg = ($typeSlug !== '' && is_file(public_path('branding/categories/'.$typeSlug.'.jpg')))
                    ? asset('branding/categories/'.$typeSlug.'.jpg')
                    : asset('branding/categories/photographer.jpg');
            @endphp
            <a
                class="srch-type {{ $slug === $typeSlug ? 'is-active' : '' }}"
                href="{{ route('site.search', array_filter(['vendor_type' => $typeSlug ?: null, 'q' => $q ?: null, 'city_id' => $cityId ?: null])) }}"
            >
                <span class="srch-type-media" style="background-image:url('{{ $typeBg }}')"></span>
                <span class="srch-type-label">
                    @include('site.partials.category-icon', ['slug' => $typeSlug])
                    {{ $type['label'] ?? $type['name'] ?? '' }}
                </span>
            </a>
        @endforeach
    </div>

    @if($cities->isNotEmpty())
        <div class="srch-cities">
            <a class="srch-city {{ ! $cityId ? 'is-active' : '' }}" href="{{ route('site.search', array_filter(['vendor_type' => $slug ?: null, 'q' => $q ?: null, 'tags' => $tagSlugs ?: null])) }}">All cities</a>
            @foreach($cities as $city)
                <a
                    class="srch-city {{ (int) $cityId === (int) $city['id'] ? 'is-active' : '' }}"
                    href="{{ route('site.search', array_filter(['vendor_type' => $slug ?: null, 'q' => $q ?: null, 'city_id' => $city['id'], 'tags' => $tagSlugs ?: null])) }}"
                >
                    {{ $city['name_en'] ?? $city['name'] }}
                </a>
            @endforeach
        </div>
    @endif

    @if($slug === 'model')
        @php
            $genderTagSlugs = collect($modelGenders ?? [])->pluck('slug')->all();
            $nonGenderTags = collect($tagSlugs)->reject(fn ($tag) => in_array($tag, $genderTagSlugs, true))->values()->all();
        @endphp
        <div class="srch-genders" role="tablist" aria-label="Model category">
            @foreach(($modelGenders ?? []) as $gender)
                @php
                    $isActive = ($activeModelGender ?? null) === $gender['slug'];
                    $genderTags = $isActive ? $nonGenderTags : array_values(array_merge($nonGenderTags, [$gender['slug']]));
                    $genderHref = route('site.search', array_filter([
                        'vendor_type' => 'model',
                        'q' => $q ?: null,
                        'city_id' => $cityId ?: null,
                        'sort' => $sort !== 'recommended' ? $sort : null,
                        'view' => $view !== 'list' ? $view : null,
                        'tags' => $genderTags ?: null,
                        'min_price' => $minPrice,
                        'max_price' => $maxPrice,
                        'min_rating' => $minRating,
                        'verified' => $verified ? 1 : null,
                    ]));
                @endphp
                <a class="srch-gender {{ $isActive ? 'is-active' : '' }}" href="{{ $genderHref }}" role="tab" aria-selected="{{ $isActive ? 'true' : 'false' }}">
                    <span class="srch-gender-ico" aria-hidden="true">
                        @if(($gender['icon'] ?? '') === 'women')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="3.2"/><path d="M12 11.2v8.3M9.2 16.2h5.6"/></svg>
                        @elseif(($gender['icon'] ?? '') === 'kids')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="7" r="2.4"/><circle cx="16" cy="8.2" r="2"/><path d="M4.8 19.2c.5-3 2.3-4.8 4.2-4.8s3.4 1.2 4 3.2M12.8 19.2c.4-2.2 1.6-3.6 3.2-3.6 1.5 0 2.7 1.2 3.2 3.6"/></svg>
                        @else
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="10.2" cy="9.2" r="3.2"/><path d="M14.2 5.2 18.8 9.8M15.8 5.2h3v3M10.2 12.4V19M7.4 16.2h5.6"/></svg>
                        @endif
                    </span>
                    {{ $gender['label'] }}
                </a>
            @endforeach
        </div>
    @endif

    @if($summary)
        <p class="srch-summary">{{ $summary }}</p>
    @endif

    @if($activeFilters !== [])
        <div class="srch-active">
            @foreach($activeFilters as $filter)
                <a class="srch-active-chip" href="{{ route('site.search', $filter['clear']) }}">
                    {{ $filter['label'] }}
                    <span aria-hidden="true">×</span>
                </a>
            @endforeach
            <a class="srch-active-clear" href="{{ route('site.search', array_filter(['vendor_type' => $slug ?: null, 'q' => $q ?: null])) }}">Clear filters</a>
        </div>
    @endif

    <div class="srch-toolbar">
        <div class="srch-actions">
            <button class="srch-pill" type="button" data-open="sort">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M8 6h12M8 12h8M8 18h4"/><path d="M4 6h.01M4 12h.01M4 18h.01"/></svg>
                Sort
            </button>
            <button class="srch-pill {{ $filterOpen ? 'is-on' : '' }}" type="button" data-open="filter">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 6h16M7 12h10M10 18h4"/></svg>
                Filter
            </button>
            <a class="srch-pill {{ $view === 'map' ? 'is-on' : '' }}" href="{{ route('site.search', array_merge($params, ['sort' => $sort, 'view' => $view === 'map' ? 'list' : 'map'])) }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 21s6-5.2 6-10a6 6 0 1 0-12 0c0 4.8 6 10 6 10z"/><circle cx="12" cy="11" r="2.2"/></svg>
                {{ $view === 'map' ? 'List' : 'Map' }}
            </a>
        </div>
        <div class="srch-meta">
            <span>{{ $count }} {{ Str::plural('result', $count) }}</span>
            <button type="button" data-open="sort">{{ $sortLabels[$sort] ?? 'Recommended' }} ▾</button>
        </div>
    </div>

    @if($view === 'map')
        <div id="cat-map" class="cat-map srch-map" data-vendors='@json($mapVendors)'></div>
    @elseif($vendors === [])
        <div class="srch-empty">
            <div class="srch-empty-visual" aria-hidden="true">
                <img src="{{ asset('branding/categories/photographer.jpg') }}" alt="">
            </div>
            <strong>No exact matches yet</strong>
            <p>Try another city, category, or price range — or ask AI Search.</p>
            <div class="srch-empty-cta">
                <button class="ai-btn" type="button" data-open="filter">Adjust filters</button>
                @if(($data['features']['ai_assistant'] ?? true))
                    <a class="ghost-btn" href="{{ route('site.ai') }}">AI Search</a>
                @endif
            </div>
        </div>
    @else
        <div class="srch-grid" data-srch-grid>
            @foreach($vendors as $vendor)
                @include('site.partials.vendor-card', ['vendor' => $vendor, 'reveal' => false])
            @endforeach
        </div>
    @endif
</section>

<div class="sheet" id="sort-sheet" hidden>
    <div class="sheet-card">
        <div class="sheet-head">
            <h2>Sort</h2>
            <button type="button" data-close>×</button>
        </div>
        @foreach($sortLabels as $key => $label)
            <a class="sheet-option {{ $sort === $key ? 'is-active' : '' }}" href="{{ route('site.search', array_merge($params, ['sort' => $key, 'view' => $view])) }}">{{ $label }}</a>
        @endforeach
    </div>
</div>

<div class="sheet" id="filter-sheet" hidden>
    <form class="sheet-card sheet-form" action="{{ route('site.search') }}" method="get">
        <div class="sheet-head">
            <div>
                <h2>Filter {{ $title }}</h2>
                <p>{{ $filterOpen ? 'Filters selected' : 'Refine results' }}</p>
            </div>
            <button type="button" data-close>×</button>
        </div>
        @if($slug !== '')
            <input type="hidden" name="vendor_type" value="{{ $slug }}">
        @endif
        @if($q !== '')
            <input type="hidden" name="q" value="{{ $q }}">
        @endif
        <input type="hidden" name="sort" value="{{ $sort }}">
        <input type="hidden" name="view" value="{{ $view }}">

        <label class="sheet-label">City</label>
        <select name="city_id">
            <option value="">All Cities</option>
            @foreach($cities as $city)
                <option value="{{ $city['id'] }}" @selected((int) $cityId === (int) $city['id'])>{{ $city['name_en'] ?? $city['name'] }}</option>
            @endforeach
        </select>

        <label class="sheet-label">Price (EGP)</label>
        <div class="sheet-row">
            <input type="number" name="min_price" min="0" placeholder="Min" value="{{ $minPrice }}">
            <input type="number" name="max_price" min="0" placeholder="Max" value="{{ $maxPrice }}">
        </div>

        <label class="sheet-label">Rating</label>
        <div class="sheet-chips">
            @foreach(['' => 'Any Rating', '4.5' => '4.5+', '4' => '4.0+', '3.5' => '3.5+'] as $value => $label)
                <label>
                    <input type="radio" name="min_rating" value="{{ $value }}" @checked($value === '' ? $minRating === null : (string) $minRating === $value || (float) $minRating === (float) $value)>
                    <span>{{ $label }}</span>
                </label>
            @endforeach
        </div>

        <label class="sheet-check">
            <input type="checkbox" name="verified" value="1" @checked($verified)>
            Verified only
        </label>

        @foreach($filterGroups as $group)
            <label class="sheet-label">{{ $group['name'] }}</label>
            <div class="sheet-chips">
                @foreach($group['options'] as $option)
                    <label>
                        <input type="checkbox" name="tags[]" value="{{ $option['slug'] }}" @checked(in_array($option['slug'], $tagSlugs, true))>
                        <span>{{ $option['label'] }}</span>
                    </label>
                @endforeach
            </div>
        @endforeach

        <div class="sheet-actions">
            <a class="ghost-btn" href="{{ route('site.search', array_filter(['vendor_type' => $slug ?: null, 'q' => $q ?: null])) }}">Reset All</a>
            <button class="ai-btn" type="submit">Show results</button>
        </div>
    </form>
</div>
@endsection
