@extends('site.layout', ['title' => $title, 'active' => $active ?? 'home'])

@php
    $slug = $slug ?? \Illuminate\Support\Str::slug($title ?? 'page');
    $hero = $hero ?? '/branding/hero-egypt-creators.jpg';
    $image = $image ?? '/branding/hero-egypt-studio.jpg';
    $gallery = $gallery ?? [];
    $sections = $sections ?? [];
    $related = [
        ['slug' => 'about', 'label' => 'About Us'],
        ['slug' => 'contact', 'label' => 'Contact Us'],
        ['slug' => 'faqs', 'label' => 'FAQs'],
        ['slug' => 'pricing', 'label' => 'Pricing & Commission'],
        ['slug' => 'privacy', 'label' => 'Privacy Policy'],
        ['slug' => 'terms', 'label' => 'Terms & Conditions'],
        ['slug' => 'resources', 'label' => 'Creator Resources'],
        ['slug' => 'guidelines', 'label' => 'Creator Guidelines'],
    ];
@endphp

@section('content')
<section class="cms-hero">
    <img class="cms-hero-bg" src="{{ $hero }}" alt="" loading="eager" decoding="async">
    <div class="cms-hero-veil"></div>
    <div class="wrap cms-hero-copy">
        <p class="cms-kicker">Lens · Help &amp; info</p>
        <h1>{{ $title }}</h1>
        @if(! empty($lede))
            <p class="cms-lede">{{ $lede }}</p>
        @endif
    </div>
</section>

@if($gallery !== [])
    <section class="cms-gallery" aria-hidden="true">
        @foreach($gallery as $shot)
            <figure>
                <img src="{{ $shot }}" alt="" loading="lazy" decoding="async">
            </figure>
        @endforeach
    </section>
@endif

<section class="wrap cms-shell">
    <aside class="cms-aside">
        <figure class="cms-aside-photo">
            <img src="{{ $image }}" alt="" loading="lazy" decoding="async">
            <figcaption>{{ $title }}</figcaption>
        </figure>
        <nav class="cms-aside-nav" aria-label="More pages">
            @foreach($related as $link)
                <a href="{{ route('site.page', $link['slug']) }}" class="{{ ($slug ?? '') === $link['slug'] ? 'is-on' : '' }}">{{ $link['label'] }}</a>
            @endforeach
            <a href="{{ route('site.help') }}" class="{{ ($slug ?? '') === 'help' ? 'is-on' : '' }}">Help &amp; Support</a>
        </nav>
    </aside>

    <div class="cms-main">
        @if(! empty($html))
            <div class="cms-intro page-rich">
                {!! $html !!}
            </div>
        @elseif(! empty($body))
            <p class="cms-intro-lede">{{ $body }}</p>
        @endif

        @if(($slug ?? '') === 'contact')
            <div class="cms-contact-grid">
                <a class="cms-contact-card" href="mailto:{{ $supportEmail ?? 'support@lens.app' }}">
                    <span class="cms-contact-ico" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path d="M4 6.5h16v11H4z"/><path d="m4.5 7 7.5 6 7.5-6"/></svg>
                    </span>
                    <strong>Email</strong>
                    <span>{{ $supportEmail ?? 'support@lens.app' }}</span>
                </a>
                <a class="cms-contact-card" href="{{ route('site.help') }}#report">
                    <span class="cms-contact-ico" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path d="M5 6h14v10H8l-3 3z"/></svg>
                    </span>
                    <strong>Report an issue</strong>
                    <span>Booking · Payment · Account · App</span>
                </a>
                <a class="cms-contact-card" href="{{ route('site.help') }}">
                    <span class="cms-contact-ico" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="8.5"/><path d="M9.4 9.4a2.6 2.6 0 0 1 5.1.8c0 1.5-2.5 2.1-2.5 3.8M12 17h.01"/></svg>
                    </span>
                    <strong>Help Center</strong>
                    <span>Guides for clients &amp; creators</span>
                </a>
            </div>
        @endif

        @if($sections !== [])
            <div class="cms-acc-head">
                <h2>{{ ($slug ?? '') === 'faqs' ? 'Common questions' : 'Explore details' }}</h2>
                <p>Tap a section to open — one at a time.</p>
            </div>
            <div class="cms-acc" data-cms-acc>
                @foreach($sections as $i => $section)
                    <details class="cms-acc-item" @if($i === 0) open @endif>
                        <summary>
                            <span class="cms-acc-index">{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</span>
                            <span class="cms-acc-title">{{ $section['title'] }}</span>
                            <span class="cms-acc-chevron" aria-hidden="true">
                                <svg viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg>
                            </span>
                        </summary>
                        <div class="cms-acc-panel">
                            @if(! empty($section['image']))
                                <figure class="cms-acc-shot">
                                    <img src="{{ $section['image'] }}" alt="" loading="lazy" decoding="async">
                                </figure>
                            @endif
                            <div class="cms-acc-copy">
                                @if(! empty($section['body']))
                                    <p>{{ $section['body'] }}</p>
                                @endif
                                @if(! empty($section['items']))
                                    <ul>
                                        @foreach($section['items'] as $item)
                                            <li>{{ $item }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        </div>
                    </details>
                @endforeach
            </div>
        @endif

        <div class="cms-cta-row">
            @if(($slug ?? '') === 'contact')
                <a class="cms-cta" href="{{ route('site.help') }}#report">Report an issue</a>
                <a class="cms-cta ghost" href="mailto:{{ $supportEmail ?? 'support@lens.app' }}">Email support</a>
            @elseif(in_array($slug ?? '', ['resources', 'guidelines', 'pricing'], true))
                <a class="cms-cta" href="{{ route('site.search') }}">Browse creators</a>
                <a class="cms-cta ghost" href="{{ route('site.help') }}">Help &amp; Support</a>
            @else
                <a class="cms-cta" href="{{ route('site.search') }}">Find creators</a>
                <a class="cms-cta ghost" href="{{ route('site.page', 'contact') }}">Contact Us</a>
            @endif
        </div>
    </div>
</section>

<section class="cms-band">
    <img class="cms-band-bg" src="{{ $image }}" alt="" loading="lazy" decoding="async">
    <div class="cms-band-veil"></div>
    <div class="wrap cms-band-copy">
        <p class="cms-kicker">Ready when you are</p>
        <h2>Find. Book. Create.</h2>
        <p>Browse verified talent across Egypt — escrow protects every marketplace booking.</p>
        <div class="cms-cta-row">
            <a class="cms-cta" href="{{ route('site.search') }}">Explore marketplace</a>
            <a class="cms-cta ghost" href="{{ route('site.help') }}">Get help</a>
        </div>
    </div>
</section>
@endsection
