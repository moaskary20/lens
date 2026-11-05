@extends('site.layout', ['title' => 'AI Search', 'active' => 'search'])

@section('content')
<section class="account-page wrap" data-reveal>
    <div class="page-head">
        <h1>✨ AI creative match</h1>
        <p class="lede">Describe the shoot — Lens finds creatives that fit.</p>
    </div>
    <form method="get" action="{{ route('site.ai') }}" class="search-bar" style="margin:18px 0">
        <span class="search-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg></span>
        <input name="q" value="{{ $q }}" placeholder="e.g. golden hour product shoot in Cairo for a skincare brand">
        <button class="ai-btn" type="submit">Search</button>
    </form>
    @unless($enabled)
        <div class="flash-err">AI assistant is temporarily offline — use classic search instead.</div>
    @endunless
    @if($summary)
        <div class="site-shell-card" style="margin-bottom:18px"><p style="margin:0">{{ $summary }}</p></div>
    @endif
    <div class="cards">
        @forelse($vendors as $vendor)
            @include('site.partials.vendor-card', ['vendor' => $vendor])
        @empty
            @if($q !== '')
                <div class="bookings-empty" style="grid-column:1/-1"><strong>No matches</strong><p>Try a broader brief.</p></div>
            @endif
        @endforelse
    </div>
</section>
@endsection
