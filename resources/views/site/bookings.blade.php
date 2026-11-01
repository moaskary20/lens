@extends('site.layout', ['title' => 'Bookings', 'active' => 'bookings'])

@section('content')
<section class="page-head wrap">
    <h1>Bookings</h1>
    <p class="lede">Manage protected sessions from the Lens app or the vendor console. New bookings start with a search.</p>
    <div class="cta-row">
        <a class="ai-btn" href="{{ route('site.search') }}">Find a creative</a>
        <a class="ghost-btn" href="/vendor/login">Vendor console</a>
        <a class="ghost-btn" href="/admin/login">Staff login</a>
    </div>
</section>
@endsection
