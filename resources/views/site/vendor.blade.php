@extends('site.layout', ['title' => $vendor['display_name'] ?? 'Creative', 'active' => 'explore'])

@php
    $minutes = (int) ($vendor['response_minutes'] ?? 45);
    $response = $minutes <= 60 ? 'Responds in 1 hour' : 'Responds in '.ceil($minutes / 60).' hours';
    $location = $vendor['location'] ?: ($vendor['city'] ?? 'Cairo, Egypt');
    $tabs = ['About', 'Specialties', 'Equipment', 'Portfolio', 'Packages', 'Reviews'];
    $phone = preg_replace('/\D+/', '', (string) ($vendor['contact_phone'] ?? ''));
    $whatsapp = preg_replace('/\D+/', '', (string) ($vendor['whatsapp'] ?? $phone));
    $portfolio = $vendor['portfolio'] ?? [];
    $packages = $vendor['packages'] ?? [];
    $stats = $stats ?? [];
    $user = $authUser ?? null;
    $starting = collect($packages)->min('price');
    $followers = collect($vendor['social'] ?? [])
        ->filter(fn ($row) => ! empty($row['followers']))
        ->values();
    $formatFollowers = function (int $count): string {
        if ($count >= 1_000_000) {
            return rtrim(rtrim(number_format($count / 1_000_000, 1), '0'), '.').'M';
        }
        if ($count >= 1_000) {
            return rtrim(rtrim(number_format($count / 1_000, 1), '0'), '.').'K';
        }

        return number_format($count);
    };
@endphp

@section('content')
<article class="vp" data-vendor-profile>
    <section class="vp-hero" style="background-image: url('{{ $vendor['cover_url'] }}')">
        <div class="wrap vp-hero-tools">
            <a class="vp-round" href="{{ url()->previous(route('site.home')) }}" aria-label="Back">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>
            </a>
            <div class="vp-hero-actions">
                @if($user)
                    <button
                        type="button"
                        class="vp-round fav-heart"
                        x-data="favoriteToggle({
                            on: {{ !empty($vendor['favorited']) ? 'true' : 'false' }},
                            url: @js(route('site.favorites.save', $vendor['id'])),
                            csrf: @js(csrf_token()),
                            loginUrl: @js(route('site.login')),
                        })"
                        :class="on && 'is-on'"
                        @click.prevent="
                            if (on) {
                                await fetch(@js(route('site.favorites.forget', $vendor['id'])), { method:'DELETE', headers:{'X-CSRF-TOKEN':csrf,'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}}).then(r => { if (r.ok) on = false; });
                            } else {
                                toggle();
                            }
                        "
                        aria-label="Favorite"
                    >
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M12 21s-7-4.4-7-10a4 4 0 0 1 7-2.5A4 4 0 0 1 19 11c0 5.6-7 10-7 10z"/></svg>
                    </button>
                @else
                    <a class="vp-round" href="{{ route('site.login') }}" aria-label="Sign in to favorite">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 21s-7-4.4-7-10a4 4 0 0 1 7-2.5A4 4 0 0 1 19 11c0 5.6-7 10-7 10z"/></svg>
                    </a>
                @endif
                <button class="vp-round" type="button" data-share aria-label="Share">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 12v7a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-7"/><path d="M12 16V4"/><path d="m7 9 5-5 5 5"/></svg>
                </button>
            </div>
        </div>
        @if(! empty($vendor['tagline']))
            <p class="vp-tagline wrap">{{ $vendor['tagline'] }}</p>
        @endif
        <button class="vp-showreel" type="button" data-showreel data-gallery="{{ e(json_encode(collect($portfolio)->pluck('url')->values()->all())) }}">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
            Watch Showreel
        </button>
    </section>

    <section class="wrap vp-identity">
        <img class="vp-avatar" src="{{ $vendor['profile_photo_url'] }}" alt="">
        <div class="vp-id-copy">
            <div class="vp-id-top">
                <div class="vp-title-row">
                    <h1>
                        {{ $vendor['display_name'] }}
                        @if(! empty($vendor['verified']))
                            <span class="vp-verified" title="Verified">✓</span>
                        @endif
                    </h1>
                    <span class="vp-available">
                        <span class="vp-dot"></span>
                        Available this weekend
                    </span>
                </div>
            </div>
            <p class="vp-profession">{{ $vendor['profession'] ?? $vendor['vendor_type_name'] }}</p>
            <p class="vp-meta">
                <svg class="vp-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 21s7-5.4 7-11a7 7 0 1 0-14 0c0 5.6 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>
                {{ $location }}
            </p>
            <p class="vp-meta">
                <span class="rating">★ {{ number_format((float) ($vendor['rating_avg'] ?? 0), 1) }}</span>
                <span>({{ $vendor['rating_count'] ?? 0 }} reviews)</span>
                <span class="vp-meta-sep">·</span>
                <svg class="vp-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M13 2 4 14h7l-1 8 10-14h-7l0-6z"/></svg>
                {{ $response }}
            </p>
            @if($starting)
                <p class="vp-from">From <strong>EGP {{ number_format((float) $starting) }}</strong></p>
            @endif
        </div>
    </section>

    @if($followers->isNotEmpty())
        <section class="wrap vp-followers" aria-label="Followers">
            @foreach($followers as $row)
                @php
                    $tag = strtolower((string) ($row['label'] ?? ''));
                    $net = str_contains($tag, 'instagram') ? 'ig' : (str_contains($tag, 'tiktok') ? 'tt' : (str_contains($tag, 'facebook') || str_contains($tag, 'meta') ? 'fb' : 'ot'));
                @endphp
                <div class="vp-follower is-{{ $net }}" role="group" aria-label="{{ $row['label'] }} followers">
                    <span class="vp-follower-ico" aria-hidden="true">
                        @if($net === 'ig')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor"/></svg>
                        @elseif($net === 'tt')
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M14.5 3c.4 2.4 1.8 4.2 4 4.7v2.4c-1.4-.1-2.7-.6-3.8-1.4v6.6c0 3.2-2.5 5.7-5.7 5.7S3.3 18.5 3.3 15.3 5.8 9.6 9 9.6c.3 0 .6 0 .9.1v2.5c-.3-.1-.6-.1-.9-.1-1.8 0-3.2 1.5-3.2 3.2S7.2 18.5 9 18.5s3.2-1.5 3.2-3.2V3h2.3z"/></svg>
                        @elseif($net === 'fb')
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M14 9h3V6h-3c-2.2 0-4 1.8-4 4v2H7v3h3v7h3v-7h3.1l.9-3H13v-2c0-.6.4-1 1-1z"/></svg>
                        @else
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        @endif
                    </span>
                    <span class="vp-follower-copy">
                        <span class="vp-follower-net">{{ $row['label'] }}</span>
                        <strong>{{ $formatFollowers((int) $row['followers']) }}</strong>
                        <small>Followers</small>
                    </span>
                </div>
            @endforeach
        </section>
    @endif

    @if($stats !== [])
        <section class="wrap vp-stats" style="--vp-stats: {{ count($stats) }}">
            @foreach($stats as $stat)
                <div><strong>{{ $stat['value'] }}</strong><span>{{ $stat['label'] }}</span></div>
            @endforeach
        </section>
    @endif

    <nav class="wrap vp-tabs" aria-label="Vendor sections">
        @foreach($tabs as $tab)
            <button type="button" data-tab="{{ $tab }}" class="{{ $tab === 'Portfolio' ? 'is-active' : '' }}">{{ $tab }}</button>
        @endforeach
    </nav>

    <div class="wrap vp-filters" data-filters>
        @foreach($vendor['portfolio_filters'] ?? ['All'] as $filter)
            <button type="button" data-filter="{{ $filter }}" class="{{ $filter === 'All' ? 'is-active' : '' }}">{{ $filter }}</button>
        @endforeach
    </div>

    <div class="wrap vp-panels">
        <section data-panel="About" hidden>
            <p class="vp-bio">{{ $vendor['bio'] }}</p>
            @if($followers->isNotEmpty())
                <div class="vp-social">
                    @foreach($followers as $row)
                        <div class="vp-social-static">
                            <strong>{{ $row['label'] }}</strong>
                            <small>{{ number_format((int) $row['followers']) }} followers</small>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        <section data-panel="Specialties" hidden>
            <div class="vp-chips">
                @forelse($vendor['specialties'] ?? [] as $item)
                    <span>{{ $item }}</span>
                @empty
                    <p class="empty">Specialties will appear here.</p>
                @endforelse
            </div>
        </section>

        <section data-panel="Equipment" hidden>
            <div class="vp-chips">
                @forelse($vendor['equipment'] ?? [] as $item)
                    <span>{{ $item }}</span>
                @empty
                    <p class="empty">Equipment will appear here.</p>
                @endforelse
            </div>
        </section>

        <section data-panel="Portfolio">
            @if(! empty($vendor['portfolio_is_showcase']))
                <p class="vp-showcase-note">Selected work in this style — book to unlock the full project gallery.</p>
            @endif
            <div class="vp-shots">
                @forelse($portfolio as $index => $shot)
                    <button
                        type="button"
                        class="shot {{ ! empty($shot['tall']) ? 'is-tall' : '' }}"
                        data-shot
                        data-category="{{ $shot['category'] ?? 'All' }}"
                        data-url="{{ $shot['url'] }}"
                        data-index="{{ $index }}"
                        style="background-image: url('{{ $shot['url'] }}')"
                        aria-label="Open {{ $shot['title'] ?? 'portfolio image' }}"
                    >
                        @if(! empty($shot['title']))
                            <span class="shot-caption">{{ $shot['title'] }}</span>
                        @endif
                        <span class="shot-zoom" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="6"/><path d="m20 20-3.2-3.2"/><path d="M11 8v6M8 11h6"/></svg>
                        </span>
                    </button>
                @empty
                    <p class="empty">No work in this collection yet.</p>
                @endforelse
            </div>
        </section>

        <section data-panel="Packages" hidden>
            <div class="vp-packages">
                @forelse($packages as $index => $package)
                    @php
                        $hours = $package['duration_hours'] ?? null;
                        $desc = $hours ? $hours.' hour'.($hours == 1 ? '' : 's').' session' : 'Flexible package';
                    @endphp
                    <article class="vp-package {{ $index === 0 ? 'is-featured' : '' }}">
                        <div class="vp-package-copy">
                            @if($index === 0)
                                <span class="vp-package-badge">Most booked</span>
                            @endif
                            <strong>{{ $package['label'] }}</strong>
                            <p>{{ $desc }} · escrow protected</p>
                        </div>
                        <div class="vp-package-side">
                            <span class="vp-package-price">EGP {{ number_format((float) ($package['price'] ?? 0)) }}</span>
                            <a class="ghost-btn" href="{{ $user ? route('site.book.start', $vendor['id']) : route('site.login') }}">Select</a>
                        </div>
                    </article>
                @empty
                    <p class="empty">Packages will appear here.</p>
                @endforelse
            </div>
        </section>

        <section data-panel="Reviews" hidden>
            <p class="vp-review-sum">
                <span class="rating">★ {{ number_format((float) ($vendor['rating_avg'] ?? 0), 1) }}</span>
                from {{ $vendor['rating_count'] ?? 0 }} reviews
            </p>
            @forelse($vendor['reviews'] ?? [] as $review)
                <article class="vp-review">
                    <strong>{{ $review['client'] }} · ★ {{ $review['rating'] }}</strong>
                    <p>{{ $review['comment'] ?: 'Clients highlight on-time delivery, art direction, and a calm on-set presence.' }}</p>
                </article>
            @empty
                <p class="vp-bio">Clients highlight on-time delivery, art direction, and a calm on-set presence.</p>
            @endforelse
        </section>
    </div>

    <div class="vp-bookbar">
        <div class="wrap vp-bookbar-inner">
            <div class="vp-bookbar-copy">
                <strong>{{ $vendor['display_name'] }}</strong>
                <span>
                    @if($starting)
                        From EGP {{ number_format((float) $starting) }}
                    @else
                        Escrow-protected booking
                    @endif
                </span>
            </div>
            @if(! empty($vendor['contact_unlocked']))
                <div class="vp-contact">
                    @if($phone)
                        <a class="vp-contact-btn dark" href="tel:{{ $phone }}">Call</a>
                    @endif
                    @if($whatsapp)
                        <a class="vp-contact-btn wa" href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener">WhatsApp</a>
                    @endif
                    <a class="ai-btn" href="{{ $user ? route('site.messages.vendor', $vendor['id']) : route('site.login') }}">Chat</a>
                </div>
            @else
                <a class="ai-btn vp-book" href="{{ $user ? route('site.book.start', $vendor['id']) : route('site.login') }}">Book Now</a>
            @endif
        </div>
    </div>
</article>

<div class="vp-lightbox" id="vp-lightbox" hidden>
    <button type="button" class="vp-lightbox-close" data-lightbox-close aria-label="Close">×</button>
    <button type="button" class="vp-lightbox-nav prev" data-lightbox-prev aria-label="Previous">‹</button>
    <img src="" alt="" data-lightbox-image>
    <button type="button" class="vp-lightbox-nav next" data-lightbox-next aria-label="Next">›</button>
</div>
@endsection
