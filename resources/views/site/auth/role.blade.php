@extends('site.layout', ['title' => 'Join Lens', 'active' => 'account'])

@section('content')
<section class="auth-page wrap" data-reveal>
    <div class="page-head">
        <h1>How will you use Lens?</h1>
        <p class="lede">Pick a path — you can always manage everything from your account later.</p>
    </div>
    <div class="site-shell-grid" style="margin-top:18px">
        <a class="site-shell-card" href="{{ route('site.register', ['role' => 'client']) }}" style="display:block">
            <h2 style="margin:0 0 8px;color:var(--orange)">I’m a client</h2>
            <p class="lede" style="margin:0">Find creatives, book packages, pay into escrow, and chat through delivery.</p>
        </a>
        <a class="site-shell-card" href="{{ route('site.vendors.join') }}" style="display:block">
            <h2 style="margin:0 0 8px;color:var(--orange)">I’m a creator</h2>
            <p class="lede" style="margin:0">Publish your portfolio, pricing, calendar, and get verified for marketplace bookings.</p>
        </a>
    </div>
</section>
@endsection
