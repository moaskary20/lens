@extends('site.layout', ['title' => 'Explore', 'active' => 'explore'])

@section('content')
@php
    $types = collect($data['vendor_types'] ?? []);
    $popular = collect($data['popular'] ?? []);
@endphp
<section class="page-head wrap">
    <h1>Explore</h1>
    <p class="lede">Browse every creative type on Lens and jump into a live shortlist.</p>
</section>
<section class="wrap block">
    <div class="cats">
        @foreach($types as $type)
            @include('site.partials.category-card', [
                'type' => $type,
                'href' => route('site.search', ['vendor_type' => $type['slug']]),
            ])
        @endforeach
        @foreach([
            ['slug' => 'ai_motion', 'label' => 'AI Videos & Motion'],
            ['slug' => 'voice', 'label' => 'VO (Voice Over)'],
        ] as $type)
            @include('site.partials.category-card', [
                'type' => $type,
                'href' => route('site.search', ['q' => $type['label']]),
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
        <div class="cards">
            @foreach(array_slice($section['vendors'] ?? [], 0, 4) as $vendor)
                @include('site.partials.vendor-card', ['vendor' => $vendor])
            @endforeach
        </div>
    </section>
@endforeach
@endsection
