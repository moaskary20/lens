@php
    $vendor = $vendor ?? [];
    $kind = $kind ?? 'creator';
    $badges = collect($vendor['badges'] ?? []);
    $top = $badges->contains('top-rated') || $badges->contains('verified');
@endphp
<article class="vendor-card">
    <a class="cover" href="{{ route('site.vendor', $vendor['id']) }}">
        <img src="{{ $vendor['cover_url'] }}" alt="{{ $vendor['display_name'] }}">
        @if($top)
            <span class="badge">Top Rated</span>
        @endif
        <span class="heart" aria-hidden="true">♡</span>
    </a>
    <div class="meta">
        <img class="face" src="{{ $vendor['profile_photo_url'] }}" alt="">
        <div>
            <strong>{{ $vendor['display_name'] }}</strong>
            <p>{{ $vendor['vendor_type_name'] ?? $kind }}</p>
            <p class="rating">★ {{ number_format((float) ($vendor['rating_avg'] ?? 0), 1) }} ({{ $vendor['rating_count'] ?? 0 }}) · {{ $vendor['city'] ?? 'Egypt' }}</p>
        </div>
    </div>
    <a class="ghost-btn" href="{{ route('site.vendor', $vendor['id']) }}">View Profile</a>
</article>
