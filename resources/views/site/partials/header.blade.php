@php
    $active = $active ?? 'home';
    $placeholder = $data['search_placeholder'] ?? 'Search photographers, studios, models...';
@endphp
<header class="site-header">
    <div class="wrap site-header-row">
        <a class="brand" href="{{ route('site.home') }}" aria-label="Lens home">
            <img class="brand-logo" src="{{ asset('branding/lenslogo_mark.png') }}" alt="Lens">
        </a>
        <nav class="site-nav" aria-label="Primary">
            <a class="{{ $active === 'home' ? 'is-active' : '' }}" href="{{ route('site.home') }}">Home</a>
            <a class="{{ $active === 'bookings' ? 'is-active' : '' }}" href="{{ route('site.bookings') }}">Bookings</a>
            <a class="{{ $active === 'search' ? 'is-active' : '' }}" href="{{ route('site.search') }}">Search</a>
            <a class="{{ $active === 'explore' ? 'is-active' : '' }}" href="{{ route('site.explore') }}">Explore</a>
        </nav>
        <form class="header-search" action="{{ route('site.search') }}" method="get" role="search">
            <label class="sr-only" for="home-q">Search</label>
            <span class="search-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            </span>
            <input id="home-q" name="q" type="search" value="{{ $q ?? '' }}" placeholder="{{ $placeholder }}">
        </form>
        <div class="site-tools">
            <a class="icon-btn" href="{{ route('site.search') }}" aria-label="Notifications">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5"/><path d="M9.5 17a2.5 2.5 0 0 0 5 0"/></svg>
            </a>
            <a class="avatar" href="/admin/login" aria-label="Sign in">L</a>
        </div>
    </div>
</header>
