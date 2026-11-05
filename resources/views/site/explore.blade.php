@extends('site.layout', ['title' => 'Explore', 'active' => 'explore'])

@section('content')
@php
    $types = collect($types ?? $data['vendor_types'] ?? []);
    $popular = collect($popular ?? $data['popular'] ?? []);
@endphp

<section class="explore-page">
    <section class="page-head wrap">
        <h1>Explore</h1>
        <p class="lede">Browse every creative type on Lens — same categories as the mobile app.</p>
        <form class="search-bar explore-search" action="{{ route('site.search') }}" method="get" role="search">
            <span class="search-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            </span>
            <input type="search" name="q" placeholder="{{ $data['search_placeholder'] ?? 'Search photographers, studios, models...' }}">
            <button class="ai-btn" type="submit">Search</button>
        </form>
    </section>

    <section class="wrap block" data-card-slider>
        <div class="block-head">
            <h2>All Categories</h2>
            <div class="block-head-actions">
                <div class="card-nav">
                    <button type="button" class="card-nav-btn" data-card-prev aria-label="Previous">‹</button>
                    <button type="button" class="card-nav-btn" data-card-next aria-label="Next">›</button>
                </div>
            </div>
        </div>
        <div class="card-rail explore-cats-rail" data-card-rail>
            @foreach($types as $type)
                @include('site.partials.category-card', [
                    'type' => [
                        'slug' => $type['slug'] ?? '',
                        'label' => $type['label'] ?? ($type['name'] ?? ''),
                    ],
                    'href' => route('site.search', ['vendor_type' => $type['slug'] ?? '']),
                ])
            @endforeach
        </div>
    </section>

    @foreach($popular as $section)
        <section class="wrap block">
            <div class="block-head">
                <h2>{{ $section['title'] }}</h2>
                <a href="{{ route('site.search', ['vendor_type' => $section['slug']]) }}">View all</a>
            </div>
            <div class="card-slider" data-card-slider>
                <button class="card-nav-btn" type="button" data-card-prev aria-label="Previous">‹</button>
                <div class="card-rail" data-card-rail>
                    @foreach($section['vendors'] ?? [] as $vendor)
                        @include('site.partials.vendor-card', ['vendor' => $vendor])
                    @endforeach
                </div>
                <button class="card-nav-btn" type="button" data-card-next aria-label="Next">›</button>
            </div>
        </section>
    @endforeach
</section>
@endsection
