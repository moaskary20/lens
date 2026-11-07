@extends('site.layout', ['title' => 'App settings', 'active' => 'account'])

@section('content')
@php
    $s = $settings['settings'] ?? $settings;
    $toggles = [
        'push_enabled' => [
            'label' => 'Push notifications',
            'copy' => 'Booking status, payouts, and account alerts',
            'ico' => 'bell',
        ],
        'chat_alerts' => [
            'label' => 'Chat alerts',
            'copy' => 'New messages from vendors and clients',
            'ico' => 'chat',
        ],
        'booking_reminders' => [
            'label' => 'Booking reminders',
            'copy' => 'Upcoming sessions and check-in nudges',
            'ico' => 'calendar',
        ],
        'review_prompts' => [
            'label' => 'Review prompts',
            'copy' => 'Ask for ratings after a completed booking',
            'ico' => 'star',
        ],
        'offers' => [
            'label' => 'Offers & promos',
            'copy' => 'Occasional Lens deals and creator tips',
            'ico' => 'tag',
        ],
    ];
@endphp
<section class="acc-sub wrap" data-reveal>
    @include('site.account.partials.sub-hero', [
        'title' => 'App settings',
        'lede' => 'Alerts, language, and preferences — same as the Lens app.',
        'image' => asset('branding/account/settings.svg'),
        'icon' => 'gear',
        'tips' => ['Synced with the app', 'Change anytime', 'Privacy first'],
    ])

    <div class="acc-sub-body">
        <form method="post" action="{{ route('site.account.settings.update') }}" class="acc-settings-form">
            @csrf

            <div class="acc-sub-card acc-settings-card">
                <header class="acc-sub-card-head">
                    <span class="acc-sub-card-ico" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 4a5 5 0 0 0-5 5v2.2c0 .8-.3 1.6-.8 2.2L5 15h14l-1.2-1.6a3.5 3.5 0 0 1-.8-2.2V9a5 5 0 0 0-5-5z"/><path d="M10 18a2 2 0 0 0 4 0"/></svg>
                    </span>
                    <div>
                        <h2>Notifications</h2>
                        <p>Choose what Lens can ping you about.</p>
                    </div>
                </header>

                <div class="acc-settings-toggles">
                    @foreach($toggles as $key => $item)
                        <label class="acc-settings-toggle">
                            <span class="acc-settings-toggle-ico" aria-hidden="true">
                                @switch($item['ico'])
                                    @case('bell')
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 4a5 5 0 0 0-5 5v2.2c0 .8-.3 1.6-.8 2.2L5 15h14l-1.2-1.6a3.5 3.5 0 0 1-.8-2.2V9a5 5 0 0 0-5-5z"/><path d="M10 18a2 2 0 0 0 4 0"/></svg>
                                        @break
                                    @case('chat')
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 6.5A2.5 2.5 0 0 1 7.5 4h9A2.5 2.5 0 0 1 19 6.5v6A2.5 2.5 0 0 1 16.5 15H11l-4 3v-3H7.5A2.5 2.5 0 0 1 5 12.5z"/></svg>
                                        @break
                                    @case('calendar')
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="4" y="5" width="16" height="15" rx="2"/><path d="M8 3v4M16 3v4M4 10h16"/></svg>
                                        @break
                                    @case('star')
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m12 4 2.4 4.9 5.4.8-3.9 3.8.9 5.4L12 16.8 7.2 19l.9-5.4L4.2 9.7l5.4-.8z"/></svg>
                                        @break
                                    @case('tag')
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 12V5.5A1.5 1.5 0 0 1 5.5 4H12l8 8-6.5 6.5z"/><circle cx="8.2" cy="8.2" r="1.2"/></svg>
                                        @break
                                @endswitch
                            </span>
                            <span class="acc-settings-toggle-copy">
                                <strong>{{ $item['label'] }}</strong>
                                <small>{{ $item['copy'] }}</small>
                            </span>
                            <input type="hidden" name="{{ $key }}" value="0">
                            <input class="acc-settings-switch" type="checkbox" name="{{ $key }}" value="1" @checked(old($key, data_get($s, $key, true))) aria-label="{{ $item['label'] }}">
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="acc-settings-lower">
                <div class="acc-sub-card acc-settings-card">
                    <header class="acc-sub-card-head">
                        <span class="acc-sub-card-ico" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 6h16M4 12h10M4 18h7"/></svg>
                        </span>
                        <div>
                            <h2>Language</h2>
                            <p>How Lens labels menus and messages.</p>
                        </div>
                    </header>
                    <label class="acc-settings-locale">
                        <span class="acc-sub-label">Preferred language</span>
                        <select name="locale">
                            <option value="en" @selected(old('locale', data_get($s, 'locale', 'en')) === 'en')>English</option>
                            <option value="ar" @selected(old('locale', data_get($s, 'locale')) === 'ar')>العربية</option>
                        </select>
                    </label>
                </div>

                <div class="acc-sub-card acc-settings-card acc-settings-legal">
                    <header class="acc-sub-card-head">
                        <span class="acc-sub-card-ico" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M7 4h10v16H7z"/><path d="M10 8h4M10 12h4M10 16h3"/></svg>
                        </span>
                        <div>
                            <h2>Policies</h2>
                            <p>Legal pages for your Lens account.</p>
                        </div>
                    </header>
                    <div class="acc-sub-links">
                        <a class="ghost-btn" href="{{ route('site.page', 'terms') }}">Terms</a>
                        <a class="ghost-btn" href="{{ route('site.page', 'privacy') }}">Privacy</a>
                    </div>
                </div>
            </div>

            <div class="acc-settings-actions">
                <button class="ai-btn" type="submit">Save settings</button>
            </div>
        </form>
    </div>
</section>
@endsection
