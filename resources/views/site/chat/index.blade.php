@extends('site.layout', ['title' => 'Messages', 'active' => 'account'])

@section('content')
<section class="chat-page wrap" data-reveal>
    <div class="page-head">
        <h1>Messages</h1>
        <p class="lede">Project threads unlocked after booking.</p>
    </div>
    <div class="inbox-list" style="margin-top:16px">
        @forelse((array) $projects as $project)
            <a class="inbox-item" href="{{ !empty($project['conversation_id']) ? route('site.messages.show', $project['conversation_id']) : route('site.messages.open') }}"
               @if(empty($project['conversation_id']) && !empty($project['vendor_id']))
                   onclick="event.preventDefault(); const f=document.getElementById('open-{{ $project['vendor_id'] }}'); f && f.submit();"
               @endif>
                <div>
                    <strong>{{ $project['title'] ?? $project['project_name'] ?? 'Project' }}</strong>
                    <p class="lede" style="margin:4px 0 0">{{ $project['counterpart'] ?? $project['vendor_name'] ?? '' }}</p>
                </div>
                <span style="color:var(--orange);font-weight:800">Chat →</span>
            </a>
            @if(empty($project['conversation_id']) && !empty($project['vendor_id']))
                <form id="open-{{ $project['vendor_id'] }}" method="post" action="{{ route('site.messages.open') }}" class="sr-only">
                    @csrf
                    <input type="hidden" name="vendor_id" value="{{ $project['vendor_id'] }}">
                    @if(!empty($project['booking_id']))
                        <input type="hidden" name="booking_id" value="{{ $project['booking_id'] }}">
                    @endif
                </form>
            @endif
        @empty
            <div class="bookings-empty"><strong>No message threads</strong><p>Book a creative to unlock in-app chat on web.</p><a class="ai-btn" href="{{ route('site.explore') }}">Browse</a></div>
        @endforelse
    </div>
</section>
@endsection
