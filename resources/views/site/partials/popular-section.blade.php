@php
    $section = $section ?? [];
    $slug = $section['slug'] ?? '';
    $vendors = array_values($section['vendors'] ?? []);
@endphp
@if($vendors !== [])
<section class="wrap block" data-card-slider data-home-rail>
    <div class="block-head" data-rail-head>
        <h2>{{ $section['title'] }}</h2>
        <div class="block-head-actions">
            <div class="card-nav">
                <button type="button" class="card-nav-btn" data-card-prev aria-label="Previous">‹</button>
                <button type="button" class="card-nav-btn" data-card-next aria-label="Next">›</button>
            </div>
            <a href="{{ route('site.search', ['vendor_type' => $slug]) }}">View all</a>
        </div>
    </div>
    <div class="card-rail" data-card-rail>
        @foreach($vendors as $vendor)
            @include('site.partials.vendor-card', ['vendor' => $vendor, 'reveal' => false])
        @endforeach
    </div>
</section>
@endif
