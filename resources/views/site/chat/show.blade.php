@extends('site.layout', ['title' => 'Chat', 'active' => 'account'])

@section('content')
@php
    $messages = $conversation['messages'] ?? [];
    $session = $conversation['session'] ?? [];
@endphp
<section class="chat-page wrap" data-reveal>
    <div class="chat-layout"
         x-data="chatThread({
            messages: @js($messages),
            pollUrl: @js(route('site.messages.poll', $conversationId)),
            sendUrl: @js(route('site.messages.store', $conversationId)),
            csrf: @js(csrf_token()),
         })">
        <aside class="chat-list">
            <div style="padding:16px;border-bottom:1px solid var(--line)">
                <strong>{{ $session['title'] ?? 'Conversation' }}</strong>
                <p class="lede" style="margin:4px 0 0">{{ $session['when'] ?? '' }}</p>
            </div>
            @foreach((array) $projects as $project)
                <a class="chat-list-item {{ (int) ($project['conversation_id'] ?? 0) === (int) $conversationId ? 'is-active' : '' }}"
                   href="{{ !empty($project['conversation_id']) ? route('site.messages.show', $project['conversation_id']) : '#' }}">
                    <div>
                        <strong>{{ $project['title'] ?? $project['project_name'] ?? 'Project' }}</strong>
                        <p class="lede" style="margin:4px 0 0;font-size:12px">{{ $project['counterpart'] ?? '' }}</p>
                    </div>
                </a>
            @endforeach
        </aside>
        <div class="chat-thread">
            <div class="chat-messages" x-ref="scroller">
                <template x-for="msg in messages" :key="msg.id">
                    <div class="chat-bubble" :class="msg.mine || msg.is_mine ? 'is-mine' : ''">
                        <div x-text="msg.body || msg.text || ''"></div>
                    </div>
                </template>
            </div>
            <form class="chat-compose" @submit.prevent="send()">
                <input type="text" x-model="body" placeholder="Write a message…" style="flex:1;background:#141518;border:1px solid #2a2d34;border-radius:999px;padding:12px 16px">
                <button class="ai-btn" type="submit" :disabled="busy">Send</button>
            </form>
        </div>
    </div>
</section>
@endsection
