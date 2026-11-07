@extends('site.layout', ['title' => $data['name'] ?? 'Lens', 'active' => 'home'])

@section('content')
@php
    // Categories = Admin → Vendor Types (enabled / marketplace), same as the app bootstrap.
    $types = collect($data['vendor_types'] ?? [])->values();
    $popular = collect($data['popular'] ?? [])->keyBy('slug');
    $typeSlugs = $types->pluck('slug')->filter()->all();
@endphp

<div class="home-shell" data-home-motion>
    <div class="home-fg">
    <section class="hero swiper swiper-hero" data-hero-swiper>
        <div class="swiper-wrapper">
            @foreach($slides as $slide)
                <article class="swiper-slide">
                    <div class="hero-media" style="background-image: url('{{ $slide['image'] }}')" aria-hidden="true"></div>
                    <div class="hero-copy wrap">
                        <p class="kicker" data-hero-line>{{ $slide['kicker'] }}</p>
                        <h1 data-hero-line>{{ $slide['title'] }}</h1>
                        <p data-hero-line>{{ $slide['body'] }}</p>
                    </div>
                </article>
            @endforeach
        </div>
        <div class="swiper-button-prev" aria-label="Previous slide"></div>
        <div class="swiper-button-next" aria-label="Next slide"></div>
        <div class="swiper-pagination"></div>
        <div class="hero-progress" aria-hidden="true">
            <span class="hero-progress-bar" data-hero-progress></span>
        </div>
    </section>

    <section class="ai-search wrap" id="ai-search" data-home-ai>
        <a class="ai-prompt" href="{{ route('site.ai') }}">
            <span class="ai-prompt-copy">
                <strong>✨ What will you create today?</strong>
                <small>{{ $data['ai_helper'] ?? 'Describe your idea and let AI find the right creatives for you.' }}</small>
            </span>
            <span class="ai-btn">✨ AI Search →</span>
        </a>
    </section>

    <section class="wrap block" data-home-cats data-card-slider>
        <div class="block-head">
            <h2>Categories</h2>
            <div class="block-head-actions home-cats-nav">
                <div class="card-nav">
                    <button type="button" class="card-nav-btn" data-card-prev aria-label="Previous categories">‹</button>
                    <button type="button" class="card-nav-btn" data-card-next aria-label="Next categories">›</button>
                </div>
            </div>
        </div>
        <div class="cats cats--home" data-card-rail>
            @foreach($types as $type)
                @include('site.partials.category-card', [
                    'type' => $type,
                    'href' => route('site.search', ['vendor_type' => $type['slug'] ?? '']),
                ])
            @endforeach
        </div>
    </section>

    @foreach($types as $type)
        @include('site.partials.popular-section', ['section' => $popular->get($type['slug'] ?? '')])
    @endforeach

    @foreach($popular as $slug => $section)
        @if(! in_array($slug, $typeSlugs, true))
            @include('site.partials.popular-section', ['section' => $section])
        @endif
    @endforeach
    </div>
</div>
@endsection
