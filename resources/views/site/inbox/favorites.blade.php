@extends('site.layout', ['title' => 'Favorites', 'active' => 'account'])

@section('content')
<section class="account-page wrap" data-reveal>
    <div class="page-head">
        <h1>Saved creators</h1>
        <p class="lede">Creatives you hearted across web and app.</p>
    </div>
    <div class="cards" style="margin-top:18px">
        @forelse((array) $favorites as $vendor)
            @include('site.partials.vendor-card', ['vendor' => $vendor])
        @empty
            <div class="bookings-empty" style="grid-column:1/-1"><strong>No favorites yet</strong><p>Tap the heart on any creative to save them here.</p><a class="ai-btn" href="{{ route('site.explore') }}">Explore</a></div>
        @endforelse
    </div>
</section>
@endsection
