@extends('site.layout', ['title' => 'Booking confirmed', 'active' => 'bookings'])

@section('content')
<section class="book-flow wrap" data-reveal>
    <div class="site-shell-card" style="max-width:640px;margin-inline:auto;text-align:center">
        <p class="kicker">You’re booked</p>
        <h1 style="margin:0 0 8px">{{ $booking->reference }}</h1>
        <p class="lede">{{ $booking->project_name }} with {{ $booking->vendor?->display_name }}</p>
        <p class="lede">{{ optional($booking->scheduled_at)->format('l, M j · H:i') }} · {{ $booking->location_text }}</p>
        <div class="cta-row" style="justify-content:center">
            <a class="ai-btn" href="{{ route('site.bookings') }}">View bookings</a>
            <a class="ghost-btn" href="{{ route('site.messages.vendor', $booking->vendor_id) }}" style="margin:0">Open chat</a>
        </div>
    </div>
</section>
@endsection
