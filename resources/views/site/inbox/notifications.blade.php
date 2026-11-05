@extends('site.layout', ['title' => 'Notifications', 'active' => 'account'])

@section('content')
<section class="account-page wrap" data-reveal>
    <div class="bookings-head">
        <div>
            <h1>Notifications</h1>
            <p class="lede">Booking updates, chat, and delivery alerts.</p>
        </div>
        <div class="cta-row">
            <form method="post" action="{{ route('site.notifications.readAll') }}">@csrf<button class="ghost-btn" type="submit" style="margin:0">Mark all read</button></form>
            <form method="post" action="{{ route('site.notifications.clearAll') }}">@csrf<button class="ghost-btn" type="submit" style="margin:0">Clear message</button></form>
        </div>
    </div>
    <div class="inbox-list">
        @forelse((array) $notifications as $item)
            <div class="inbox-item {{ empty($item['read']) ? 'is-unread' : '' }}">
                <div>
                    <strong>{{ $item['title'] ?? $item['type'] ?? 'Update' }}</strong>
                    <p class="lede" style="margin:4px 0 0">{{ $item['body'] ?? $item['message'] ?? '' }}</p>
                    <p class="lede" style="margin:4px 0 0;font-size:12px">{{ $item['created_at'] ?? $item['when'] ?? '' }}</p>
                </div>
                @if(!empty($item['id']) && empty($item['read']))
                    <form method="post" action="{{ route('site.notifications.read', $item['id']) }}">@csrf<button class="ghost-btn" type="submit" style="margin:0">Read</button></form>
                @endif
            </div>
        @empty
            <div class="bookings-empty"><strong>Inbox zero</strong><p>You’re all caught up.</p></div>
        @endforelse
    </div>
</section>
@endsection
