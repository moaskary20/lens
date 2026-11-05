@extends('site.layout', ['title' => 'Account', 'active' => 'account'])

@section('content')
<section class="account-page wrap" data-reveal>
    <div class="page-head">
        <h1>{{ $user->name }}</h1>
        <p class="lede">{{ $user->email }} · {{ $isVendor ? 'Creator' : 'Client' }}</p>
    </div>

    <div class="site-shell-grid" style="margin-top:18px">
        <nav class="account-nav">
            <a href="{{ route('site.account.edit') }}">Edit profile <span>→</span></a>
            @if($isVendor)
                <a href="{{ route('site.vendors.profile') }}">Creator studio <span>→</span></a>
            @endif
            <a href="{{ route('site.bookings') }}">My bookings <span>→</span></a>
            <a href="{{ route('site.messages') }}">Messages <span>→</span></a>
            <a href="{{ route('site.favorites') }}">Saved creators <span>→</span></a>
            <a href="{{ route('site.notifications') }}">Notifications <span>→</span></a>
            <a href="{{ route('site.account.wallet') }}">Wallet <span>→</span></a>
            <a href="{{ route('site.account.payments') }}">Payment methods <span>→</span></a>
            <a href="{{ route('site.account.addresses') }}">Addresses <span>→</span></a>
            <a href="{{ route('site.account.settings') }}">App settings <span>→</span></a>
            <a href="{{ route('site.account.report') }}">Report an issue <span>→</span></a>
            @unless($isVendor)
                <a href="{{ route('site.vendors.join') }}">Become a creator <span>→</span></a>
            @endunless
            <form method="post" action="{{ route('site.logout') }}">
                @csrf
                <button class="ghost-btn" type="submit" style="width:100%;margin:8px 0 0">Log out</button>
            </form>
        </nav>
        <aside class="site-shell-card">
            <h3 style="margin-top:0">Quick actions</h3>
            <div class="cta-row">
                <a class="ai-btn" href="{{ route('site.search') }}">Find creatives</a>
                <a class="ghost-btn" href="{{ route('site.explore') }}" style="margin:0">Explore</a>
            </div>
            <p class="lede" style="margin-top:16px">Your Lens web account mirrors the mobile app — bookings, chat, wallet and favorites stay in sync.</p>
        </aside>
    </div>
</section>
@endsection
