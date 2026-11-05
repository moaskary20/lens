@php
    $vendor = $vendor ?? [];
    $location = $vendor['location'] ?: ($vendor['city'] ?? 'Egypt');
    $thumb = $vendor['cover_url'] ?? $vendor['profile_photo_url'] ?? null;
@endphp
<a class="listing-row" href="{{ route('site.vendor', $vendor['id']) }}">
    @if(filled($thumb))
        <img class="listing-thumb" src="{{ $thumb }}" alt="" loading="lazy">
    @else
        <span class="listing-thumb" aria-hidden="true"></span>
    @endif
    <div class="listing-copy">
        <div class="listing-top">
            <strong>{{ $vendor['display_name'] }}</strong>
            @if(! empty($vendor['verified']))
                <span class="listing-verified" title="Verified">✓</span>
            @endif
        </div>
        <p class="listing-rating">★ {{ number_format((float) ($vendor['rating_avg'] ?? 0), 1) }} <span>({{ $vendor['rating_count'] ?? 0 }})</span></p>
        @if(! empty($vendor['tags']))
            <p class="listing-tags">
                @foreach(array_slice($vendor['tags'], 0, 3) as $tag)
                    <span>{{ $tag }}</span>
                @endforeach
            </p>
        @endif
        <div class="listing-foot">
            <p>📍 {{ $location }}</p>
            @if(! empty($vendor['starting_from']))
                <div class="listing-price">
                    <small>Starting from</small>
                    <strong>EGP {{ number_format((float) $vendor['starting_from']) }}</strong>
                </div>
            @endif
        </div>
    </div>
</a>
