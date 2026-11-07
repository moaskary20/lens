@extends('site.layout', ['title' => 'Account', 'active' => 'account'])

@php
    $avatar = $user->avatarUrl();
    $roleLabel = $isVendor ? 'Creator' : 'Client';
    $tiles = [
        [
            'href' => route('site.bookings'),
            'label' => 'My bookings',
            'copy' => 'Sessions, status, and deliverables',
            'image' => asset('branding/account/bookings.svg'),
        ],
        [
            'href' => route('site.messages'),
            'label' => 'Messages',
            'copy' => 'Chat with creatives and clients',
            'image' => asset('branding/account/messages.svg'),
        ],
        [
            'href' => route('site.favorites'),
            'label' => 'Saved creators',
            'copy' => 'Your shortlist for the next shoot',
            'image' => asset('branding/account/favorites.svg'),
        ],
        [
            'href' => route('site.account.wallet'),
            'label' => 'Wallet',
            'copy' => 'Balance, earnings, and history',
            'image' => asset('branding/account/wallet.svg'),
        ],
        [
            'href' => route('site.notifications'),
            'label' => 'Notifications',
            'copy' => 'Booking updates and payouts',
            'image' => asset('branding/account/notifications.svg'),
        ],
        [
            'href' => route('site.search'),
            'label' => 'Find creatives',
            'copy' => 'Search photographers, studios, models',
            'image' => asset('branding/account/search.svg'),
        ],
    ];
    $links = [
        [
            'href' => route('site.account.edit'),
            'label' => 'Edit profile',
            'copy' => 'Name, photo, and contact details',
            'icon' => 'profile',
        ],
        [
            'href' => route('site.account.payments'),
            'label' => 'Payment methods',
            'copy' => 'Cards and wallets for checkout',
            'icon' => 'card',
        ],
        [
            'href' => route('site.account.addresses'),
            'label' => 'Addresses',
            'copy' => 'Shoot locations you use often',
            'icon' => 'pin',
        ],
        [
            'href' => route('site.account.settings'),
            'label' => 'App settings',
            'copy' => 'Language, alerts, and preferences',
            'icon' => 'gear',
        ],
        [
            'href' => route('site.account.report'),
            'label' => 'Report an issue',
            'copy' => 'Tell Lens about a booking or bug',
            'icon' => 'flag',
        ],
    ];
    if ($isVendor) {
        array_unshift($links, [
            'href' => route('site.vendors.profile'),
            'label' => 'Creator studio',
            'copy' => 'Portfolio, packages, and payouts',
            'icon' => 'studio',
        ]);
    } else {
        $links[] = [
            'href' => route('site.vendors.join'),
            'label' => 'Become a creator',
            'copy' => 'Publish your work on Lens',
            'icon' => 'spark',
        ];
    }
@endphp

@section('content')
<section class="acc-hero" data-account-motion>
    <img class="acc-hero-bg" src="{{ asset('branding/hero-egypt-creators.jpg') }}" alt="" loading="eager" decoding="async" data-acc-media>
    <div class="acc-hero-veil" aria-hidden="true"></div>
    <div class="wrap acc-hero-copy" data-acc-copy>
        <p class="acc-kicker">Lens · {{ $roleLabel }}</p>
        <div class="acc-identity">
            @if($avatar)
                <img class="acc-avatar" src="{{ $avatar }}" alt="" width="72" height="72">
            @else
                <span class="acc-avatar acc-avatar-fallback" aria-hidden="true">{{ mb_strtoupper(mb_substr($user->name ?: 'L', 0, 1)) }}</span>
            @endif
            <div>
                <h1>{{ $user->name }}</h1>
                <p class="acc-lede">{{ $user->email }} · Manage bookings, chat, wallet, and settings — synced with the app.</p>
            </div>
        </div>
        <div class="acc-hero-cta">
            <a class="ai-btn" href="{{ route('site.account.edit') }}">Edit profile</a>
            <a class="ghost-btn" href="{{ route('site.explore') }}">Explore</a>
        </div>
    </div>
</section>

<section class="wrap acc-hub">
    <header class="acc-hub-head" data-acc-head>
        <h2>Your Lens hub</h2>
        <p>Jump into the tools you use most.</p>
    </header>

    <div class="acc-tiles" data-acc-tiles>
        @foreach($tiles as $tile)
            <a class="acc-tile" href="{{ $tile['href'] }}" data-acc-tile>
                <img src="{{ $tile['image'] }}" alt="" loading="lazy" decoding="async">
                <span class="acc-tile-veil"></span>
                <span class="acc-tile-copy">
                    <strong>{{ $tile['label'] }}</strong>
                    <small>{{ $tile['copy'] }}</small>
                </span>
            </a>
        @endforeach
    </div>

    <div class="acc-lower" data-acc-secondary>
        <div class="acc-secondary">
            <header class="acc-secondary-head">
                <h3>Account &amp; support</h3>
                <p>Profile, payments, and help — same as the app.</p>
            </header>
            <nav class="acc-link-list" aria-label="Account links">
                @foreach($links as $link)
                    <a class="acc-link" href="{{ $link['href'] }}">
                        <span class="acc-link-ico" aria-hidden="true">
                            @switch($link['icon'])
                                @case('studio')
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="14" rx="3"/><circle cx="12" cy="12" r="3.2"/><path d="M8 5V3m8 2V3"/></svg>
                                    @break
                                @case('profile')
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="3.2"/><path d="M5.5 19c1.8-3.4 4-5 6.5-5s4.7 1.6 6.5 5"/></svg>
                                    @break
                                @case('card')
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="6" width="18" height="12" rx="2.5"/><path d="M3 10h18M7 15h4"/></svg>
                                    @break
                                @case('pin')
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 21s6-5.2 6-10a6 6 0 1 0-12 0c0 4.8 6 10 6 10z"/><circle cx="12" cy="11" r="2.2"/></svg>
                                    @break
                                @case('gear')
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9c.3.6.9 1 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/></svg>
                                    @break
                                @case('flag')
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 21V4m0 0h10l-2 4 2 4H5"/></svg>
                                    @break
                                @case('spark')
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3v4M12 17v4M3 12h4M17 12h4M6.2 6.2l2.8 2.8M15 15l2.8 2.8M17.8 6.2 15 9M9 15l-2.8 2.8"/><circle cx="12" cy="12" r="2.4"/></svg>
                                    @break
                            @endswitch
                        </span>
                        <span class="acc-link-text">
                            <strong>{{ $link['label'] }}</strong>
                            <small>{{ $link['copy'] }}</small>
                        </span>
                        <span class="acc-link-arrow" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 6l6 6-6 6"/></svg>
                        </span>
                    </a>
                @endforeach
            </nav>
            <form method="post" action="{{ route('site.logout') }}" class="acc-logout">
                @csrf
                <button class="acc-logout-btn" type="submit">
                    <span class="acc-link-ico" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M10 7V5a2 2 0 0 1 2-2h7a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-7a2 2 0 0 1-2-2v-2"/><path d="M15 12H3m0 0 3-3m-3 3 3 3"/></svg>
                    </span>
                    <span>Log out</span>
                </button>
            </form>
        </div>

        <aside class="acc-aside">
            <div class="acc-aside-photo">
                <img src="{{ asset('branding/hero-egypt-studio.jpg') }}" alt="" loading="lazy" decoding="async">
                <div class="acc-aside-veil"></div>
                <div class="acc-aside-copy">
                    <p class="acc-kicker">Lens Egypt</p>
                    <h3>Ready for the next shoot?</h3>
                    <p>Browse verified creatives and book with escrow protection.</p>
                    <div class="acc-aside-cta">
                        <a class="ai-btn" href="{{ route('site.search') }}">Find creatives</a>
                        <a class="ghost-btn" href="{{ route('site.bookings') }}">My bookings</a>
                    </div>
                </div>
            </div>
            <div class="acc-aside-tips">
                <a href="{{ route('site.page', 'pricing') }}">
                    <strong>Pricing &amp; fees</strong>
                    <small>How escrow and commission work</small>
                </a>
                <a href="{{ route('site.help') }}">
                    <strong>Help &amp; support</strong>
                    <small>Disputes, payouts, and app issues</small>
                </a>
                <a href="{{ route('site.explore') }}">
                    <strong>Explore categories</strong>
                    <small>Photographers, studios, models, and more</small>
                </a>
            </div>
        </aside>
    </div>
</section>
@endsection
