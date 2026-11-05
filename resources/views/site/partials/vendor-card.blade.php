@php
    $vendor = $vendor ?? [];
    $kind = $kind ?? 'creator';
    $badges = collect($vendor['badges'] ?? []);
    $top = $badges->contains('top-rated') || $badges->contains('verified');
    $user = $authUser ?? null;
    $reveal = $reveal ?? true;
    $cover = $vendor['cover_url'] ?? $vendor['cover_image'] ?? null;
    $face = $vendor['profile_photo_url'] ?? $vendor['profile_photo'] ?? null;
@endphp
<article class="vendor-card" @if($reveal) data-reveal @endif>
    <a class="cover" href="{{ route('site.vendor', $vendor['id']) }}">
        @if(filled($cover))
            <img src="{{ $cover }}" alt="{{ $vendor['display_name'] }}" loading="lazy">
        @endif
        @if($top)
            <span class="badge">Top Rated</span>
        @endif
    </a>
    @if($user)
        <button
            type="button"
            class="heart fav-heart"
            style="position:absolute;right:10px;top:10px;z-index:2"
            x-data="favoriteToggle({
                on: {{ !empty($vendor['favorited']) ? 'true' : 'false' }},
                url: @js(route('site.favorites.save', $vendor['id'])),
                csrf: @js(csrf_token()),
                loginUrl: @js(route('site.login')),
            })"
            :class="on && 'is-on'"
            @click.prevent="
                if (on) {
                    await fetch(@js(route('site.favorites.forget', $vendor['id'])), { method:'DELETE', headers:{'X-CSRF-TOKEN':csrf,'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}}).then(r => { if(r.ok) on=false; });
                } else {
                    toggle();
                }
            "
            aria-label="Favorite"
        >♥</button>
    @else
        <a class="heart" href="{{ route('site.login') }}" aria-label="Sign in to favorite">♡</a>
    @endif
    <div class="meta">
        @if(filled($face))
            <img class="face" src="{{ $face }}" alt="" loading="lazy">
        @endif
        <div>
            <strong>{{ $vendor['display_name'] }}</strong>
            <p>{{ $vendor['vendor_type_name'] ?? $kind }}</p>
            <p class="rating">★ {{ number_format((float) ($vendor['rating_avg'] ?? 0), 1) }} ({{ $vendor['rating_count'] ?? 0 }}) · {{ $vendor['city'] ?? 'Egypt' }}</p>
        </div>
    </div>
    <a class="ghost-btn" href="{{ route('site.vendor', $vendor['id']) }}">View Profile</a>
</article>
