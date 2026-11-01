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
@endphp

@section('content')
<section class="cat-page wrap" data-category-page>
    <div class="cat-head">
        <a class="vp-round" href="{{ $slug ? route('site.explore') : route('site.home') }}" aria-label="Back">‹</a>
        <h1>{{ $title }}</h1>
    </div>

    @if($summary)
        <p class="summary">{{ $summary }}</p>
    @endif

    <div class="cat-actions">
        <button class="cat-pill" type="button" data-open="sort">⇅ Sort</button>
        <button class="cat-pill {{ $filterOpen ? 'is-on' : '' }}" type="button" data-open="filter">⚙ Filter</button>
        <a class="cat-pill {{ $view === 'map' ? 'is-on' : '' }}" href="{{ route('site.search', array_merge($params, ['sort' => $sort, 'view' => $view === 'map' ? 'list' : 'map'])) }}">📍 Map</a>
    </div>

    <div class="cat-count">
        <span>{{ $count }} {{ $title }}</span>
        <button type="button" data-open="sort">{{ $sortLabels[$sort] ?? 'Recommended' }} ▾</button>
    </div>

    @if($view === 'map')
        <div id="cat-map" class="cat-map" data-vendors='@json($mapVendors)'></div>
    @elseif($vendors === [])
        <div class="cat-empty">
            <strong>No exact matches yet</strong>
            <p>Try adjusting your choices and we’ll find more creatives for you.</p>
            <button class="ai-btn" type="button" data-open="filter">Adjust filters</button>
        </div>
    @else
        <div class="listing">
            @foreach($vendors as $vendor)
                @include('site.partials.vendor-row', ['vendor' => $vendor])
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
                <option value="{{ $city['id'] }}" @selected((int) $cityId === (int) $city['id'])>{{ $city['name'] }}</option>
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
