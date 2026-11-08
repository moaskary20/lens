@extends('site.layout', ['title' => 'Bookings', 'active' => 'bookings'])

@section('content')
<section class="bookings-page wrap">
    <div class="bookings-head">
        <div>
            <h1>Bookings</h1>
            <p class="lede">
                @if($user)
                    Signed in as {{ $user->name }} · {{ $role === 'vendor' ? 'Creator desk' : 'Client sessions' }}
                @else
                    Sign in to manage protected sessions — same flow as the Lens app.
                @endif
            </p>
        </div>
        @if($user)
            <form action="{{ route('site.bookings.logout') }}" method="post">
                @csrf
                <button class="ghost-btn" type="submit">Sign out</button>
            </form>
        @endif
    </div>

    @if(! $user)
        <div class="bookings-auth">
            <div class="bookings-auth-card">
                <h2>Sign in to continue</h2>
                <p>Use your Lens client or creator account to manage bookings on web — same as the app.</p>
                <div class="cta-row" style="margin-top:18px">
                    <a class="ai-btn" href="{{ route('site.login') }}">Sign In</a>
                    <a class="ghost-btn" href="{{ route('site.join') }}" style="margin:0">Create account</a>
                </div>
                <div class="bookings-auth-links">
                    <a href="{{ route('site.search') }}">Browse creatives</a>
                    <a href="{{ route('site.vendors.join') }}">Join as vendor</a>
                </div>
            </div>
            <div class="bookings-auth-side">
                <h3>What you can do</h3>
                <ul>
                    <li>Book creatives with escrow protection</li>
                    <li>Chat, approve deliveries, open disputes</li>
                    <li>Creators manage new requests and uploads</li>
                    <li>Wallet and payouts stay in sync with the app</li>
                </ul>
            </div>
        </div>
    @else
        <div class="bookings-tabs" role="tablist">
            @foreach([
                'upcoming' => $role === 'vendor' ? 'Active' : 'Upcoming',
                'completed' => $role === 'vendor' ? 'Delivered' : 'Completed',
                'canceled' => 'Canceled',
            ] as $key => $label)
                <a
                    class="bookings-tab {{ $tab === $key ? 'is-active' : '' }}"
                    href="{{ route('site.bookings', ['tab' => $key]) }}"
                >
                    {{ $label }}
                    <span>{{ $summary[$key] ?? 0 }}</span>
                </a>
            @endforeach
        </div>

        @if($bookings === [])
            <div class="bookings-empty">
                <strong>No {{ $tab }} bookings</strong>
                <p>
                    @if($tab === 'upcoming')
                        Find a creative and book your next session.
                    @else
                        Nothing in this list yet.
                    @endif
                </p>
                <a class="ai-btn" href="{{ route('site.search') }}">Find a creative</a>
            </div>
        @else
            <div class="bookings-list">
                @foreach($bookings as $booking)
                    <article class="booking-card">
                        <div class="booking-card-main">
                            @if(! empty($booking['photo']))
                                <img class="booking-photo" src="{{ $booking['photo'] }}" alt="">
                            @else
                                <div class="booking-photo booking-photo-fallback">{{ mb_substr((string) ($booking['counterpart'] ?? 'L'), 0, 1) }}</div>
                            @endif
                            <div class="booking-copy">
                                <div class="booking-top">
                                    <strong>{{ $booking['counterpart'] }}</strong>
                                    <span class="booking-badge">{{ $booking['badge'] }}</span>
                                </div>
                                <p class="booking-meta">{{ $booking['project_name'] }} · {{ $booking['reference'] }}</p>
                                <p class="booking-when">{{ $booking['date_label'] ?? $booking['when'] }}</p>
                                @if(! empty($booking['time_label']))
                                    <p class="booking-time">{{ $booking['time_label'] }}</p>
                                @endif
                                <p class="booking-loc">{{ $booking['location'] }}</p>
                            </div>
                        </div>
                        <div class="booking-card-side">
                            <div class="booking-total">
                                <small>Total</small>
                                <strong>EGP {{ number_format((float) ($booking['total'] ?? 0), 2) }}</strong>
                            </div>
                            <p class="booking-status">{{ $booking['status_label'] }}</p>
                            <div class="cta-row" style="justify-content:flex-end">
                                <a class="ghost-btn" href="{{ route('site.bookings.deliverables', $booking['id']) }}" style="margin:0">Files</a>
                                @if($role === 'client' && ! empty($booking['vendor_id']))
                                    <a class="ghost-btn" href="{{ route('site.vendor', $booking['vendor_id']) }}" style="margin:0">Profile</a>
                                    <a class="ghost-btn" href="{{ route('site.messages.vendor', $booking['vendor_id']) }}" style="margin:0">Chat</a>
                                @endif
                                @if($role === 'vendor')
                                    <form method="post" action="{{ route('site.bookings.cancel', $booking['id']) }}">@csrf<button class="ghost-btn" type="submit" style="margin:0">Cancel</button></form>
                                @endif
                                @if(! in_array($booking['status'] ?? '', ['cancelled', 'failed', 'refunded', 'approved', 'completed'], true))
                                    <details class="booking-dispute-form">
                                        <summary class="ghost-btn" style="margin:0;cursor:pointer;list-style:none">Open dispute</summary>
                                        <form method="post" action="{{ route('site.bookings.dispute', $booking['id']) }}" class="site-shell-form" style="margin-top:10px;min-width:220px">
                                            @csrf
                                            <input type="hidden" name="kind" value="dispute">
                                            <label for="dispute-reason-{{ $booking['id'] }}">What happened?</label>
                                            <textarea id="dispute-reason-{{ $booking['id'] }}" name="reason" rows="3" required maxlength="2000" placeholder="Describe the issue for Lens support"></textarea>
                                            <button class="ghost-btn" type="submit" style="margin:0;color:#ff5a1f;border-color:#ff5a1f">Send dispute</button>
                                        </form>
                                    </details>
                                @endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    @endif
</section>
@endsection
