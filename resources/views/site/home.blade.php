@extends('site.layout', ['title' => $data['name'] ?? 'Lens', 'active' => 'home'])

@section('content')
@php
    $types = collect($data['vendor_types'] ?? []);
    $popular = collect($data['popular'] ?? [])->keyBy('slug');
    $order = ['photographer', 'videographer', 'studio', 'reels', 'model', 'ugc', 'food_stylist'];
    $extra = [
        ['slug' => 'ai_motion', 'label' => 'AI Videos & Motion'],
        ['slug' => 'voice', 'label' => 'VO (Voice Over)'],
    ];
@endphp

<section class="hero" data-slider>
    <div class="hero-track">
        @foreach($slides as $index => $slide)
            <article class="hero-slide {{ $index === 0 ? 'is-active' : '' }}" style="background-image: url('{{ $slide['image'] }}')">
                <div class="hero-copy wrap">
                    <p class="kicker">{{ $slide['kicker'] }}</p>
                    <h1>{{ $slide['title'] }}</h1>
                    <p>{{ $slide['body'] }}</p>
                </div>
            </article>
        @endforeach
    </div>
    <button class="hero-nav prev" type="button" data-prev aria-label="Previous slide">‹</button>
    <button class="hero-nav next" type="button" data-next aria-label="Next slide">›</button>
    <div class="hero-dots" data-dots>
        @foreach($slides as $index => $slide)
            <button type="button" class="{{ $index === 0 ? 'is-active' : '' }}" data-dot="{{ $index }}" aria-label="Slide {{ $index + 1 }}"></button>
        @endforeach
    </div>
</section>

<section class="ai-search wrap" id="ai-search">
    <a class="ai-prompt" href="{{ route('site.search', ['ai' => 1]) }}">
        <span class="ai-prompt-copy">
            <strong>✨ What will you create today?</strong>
            <small>{{ $data['ai_helper'] ?? 'Describe your idea and let AI find the right creatives for you.' }}</small>
        </span>
        <span class="ai-btn">✨ AI Search →</span>
    </a>
</section>

<section class="wrap block">
    <div class="block-head">
        <h2>Categories</h2>
        <a href="{{ route('site.explore') }}">View all →</a>
    </div>
    <div class="cats">
        @foreach($types as $type)
            @include('site.partials.category-card', [
                'type' => $type,
                'href' => route('site.search', ['vendor_type' => $type['slug']]),
            ])
        @endforeach
        @foreach($extra as $type)
            @include('site.partials.category-card', [
                'type' => $type,
                'href' => route('site.search', ['q' => $type['label']]),
            ])
        @endforeach
    </div>
</section>

@foreach($order as $slug)
    @include('site.partials.popular-section', ['section' => $popular->get($slug)])
@endforeach

@foreach($popular as $slug => $section)
    @if(! in_array($slug, $order, true))
        @include('site.partials.popular-section', ['section' => $section])
    @endif
@endforeach
@endsection
