@extends('site.layout', ['title' => $vendor['display_name'] ?? 'Creative', 'active' => 'explore'])

@php
    $minutes = (int) ($vendor['response_minutes'] ?? 45);
    $response = $minutes <= 60 ? 'Responds in 1 hour' : 'Responds in '.ceil($minutes / 60).' hours';
    $location = $vendor['location'] ?: ($vendor['city'] ?? 'Cairo, Egypt');
    $tabs = ['About', 'Specialties', 'Equipment', 'Portfolio', 'Packages', 'Reviews'];
    $phone = preg_replace('/\D+/', '', (string) ($vendor['contact_phone'] ?? ''));
    $whatsapp = preg_replace('/\D+/', '', (string) ($vendor['whatsapp'] ?? $phone));
@endphp

@section('content')
<article class="vp" data-vendor-profile>
    <section class="vp-hero" style="background-image: url('{{ $vendor['cover_url'] }}')">
        <div class="wrap vp-hero-tools">
            <a class="vp-round" href="{{ url()->previous(route('site.home')) }}" aria-label="Back">‹</a>
            <div class="vp-hero-actions">
                <button class="vp-round" type="button" data-share aria-label="Share">⇪</button>
            </div>
        </div>
        @if(! empty($vendor['tagline']))
            <p class="vp-tagline wrap">{{ $vendor['tagline'] }}</p>
        @endif
        <button class="vp-showreel" type="button" data-showreel>
            ▶ Watch Showreel
        </button>
    </section>

    <section class="wrap vp-identity">
        <img class="vp-avatar" src="{{ $vendor['profile_photo_url'] }}" alt="">
        <div class="vp-id-copy">
            <div class="vp-id-top">
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
            <p class="vp-profession">{{ $vendor['profession'] ?? $vendor['vendor_type_name'] }}</p>
            <p class="vp-meta"><span>📍</span> {{ $location }}</p>
            <p class="vp-meta">
                <span class="rating">★ {{ number_format((float) ($vendor['rating_avg'] ?? 0), 1) }}</span>
                <span>({{ $vendor['rating_count'] ?? 0 }} reviews)</span>
                <span>·</span>
                <span>⚡ {{ $response }}</span>
            </p>
        </div>
    </section>

    <section class="wrap vp-stats">
        <div><strong>{{ $vendor['completed_sessions'] ?? 0 }}</strong><span>Projects Completed</span></div>
        <div><strong>{{ $vendor['assisted_sessions'] ?? 0 }}</strong><span>Assisted</span></div>
        <div><strong>{{ $vendor['years_experience'] ?? 3 }}+</strong><span>Years Experience</span></div>
        <div><strong>{{ $vendor['client_satisfaction'] ?? 90 }}%</strong><span>Client Satisfaction</span></div>
    </section>

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
            @if(! empty($vendor['social']))
                <div class="vp-social">
                    @foreach($vendor['social'] as $row)
                        <a href="{{ $row['url'] }}" target="_blank" rel="noopener">
                            {{ $row['label'] }}
                            @if(! empty($row['followers']))
                                <small>{{ number_format((int) $row['followers']) }} followers</small>
                            @endif
                        </a>
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
            <div class="vp-shots">
                @forelse($vendor['portfolio'] ?? [] as $shot)
                    <figure class="shot {{ ! empty($shot['tall']) ? 'is-tall' : '' }}" data-shot data-category="{{ $shot['category'] ?? 'All' }}" style="background-image: url('{{ $shot['url'] }}')">
                        @if(! empty($shot['title']))
                            <figcaption>{{ $shot['title'] }}</figcaption>
                        @endif
                    </figure>
                @empty
                    <p class="empty">No work in this collection yet.</p>
                @endforelse
            </div>
        </section>

        <section data-panel="Packages" hidden>
            <div class="vp-packages">
                @forelse($vendor['packages'] ?? [] as $package)
                    <div class="vp-package">
                        <strong>{{ $package['label'] }}</strong>
                        <span>EGP {{ number_format((float) ($package['price'] ?? 0)) }}</span>
                    </div>
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
        <div class="wrap">
            @if(! empty($vendor['contact_unlocked']))
                <div class="vp-contact">
                    @if($phone)
                        <a class="vp-contact-btn dark" href="tel:{{ $phone }}">Call Now</a>
                    @endif
                    @if($whatsapp)
                        <a class="vp-contact-btn wa" href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener">WhatsApp</a>
                    @endif
                    <a class="ai-btn" href="/admin/login">Chat in App</a>
                </div>
            @else
                <a class="ai-btn vp-book" href="/admin/login">Book Now</a>
            @endif
        </div>
    </div>
</article>
@endsection
