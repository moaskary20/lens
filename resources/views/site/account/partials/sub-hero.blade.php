@php
    $title = $title ?? 'Account';
    $lede = $lede ?? '';
    $image = $image ?? asset('branding/account/profile.svg');
    $icon = $icon ?? 'profile';
    $tips = $tips ?? [];
@endphp
<header class="acc-sub-hero">
    <div class="acc-sub-hero-copy">
        <a class="acc-sub-back" href="{{ route('site.account') }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg>
            Account
        </a>
        <div class="acc-sub-title-row">
            <span class="acc-sub-ico" aria-hidden="true">
                @switch($icon)
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
                @endswitch
            </span>
            <div>
                <h1>{{ $title }}</h1>
                @if($lede !== '')
                    <p>{{ $lede }}</p>
                @endif
            </div>
        </div>
        @if($tips !== [])
            <ul class="acc-sub-tips">
                @foreach($tips as $tip)
                    <li>{{ $tip }}</li>
                @endforeach
            </ul>
        @endif
    </div>
    <figure class="acc-sub-hero-art" aria-hidden="true">
        <img src="{{ $image }}" alt="" loading="eager" decoding="async">
    </figure>
</header>
