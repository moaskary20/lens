@php
    $active = $active ?? 'home';
    $placeholder = $data['search_placeholder'] ?? 'Search photographers, studios, models...';
    $user = $authUser ?? null;
    $initial = $user ? mb_strtoupper(mb_substr($user->name ?: 'L', 0, 1)) : 'L';
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
            @if($user)
                <a class="icon-btn" href="{{ route('site.favorites') }}" aria-label="Favorites" title="Favorites">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 21s-7-4.4-7-10a4 4 0 0 1 7-2.5A4 4 0 0 1 19 11c0 5.6-7 10-7 10z"/></svg>
                </a>
                <a class="icon-btn" href="{{ route('site.notifications') }}" aria-label="Notifications">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5"/><path d="M9.5 17a2.5 2.5 0 0 0 5 0"/></svg>
                    @if(($data['unread_notifications'] ?? 0) > 0)
                        <span class="badge-count">{{ ($data['unread_notifications'] ?? 0) > 9 ? '9+' : $data['unread_notifications'] }}</span>
                    @endif
                </a>
                <a class="avatar" href="{{ route('site.account') }}" aria-label="Account">{{ $initial }}</a>
            @else
                <a class="icon-btn" href="{{ route('site.login') }}" aria-label="Notifications">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5"/><path d="M9.5 17a2.5 2.5 0 0 0 5 0"/></svg>
                </a>
                <a class="avatar" href="{{ route('site.login') }}" aria-label="Sign in">{{ $initial }}</a>
            @endif
        </div>
    </div>
</header>
