@extends('site.layout', ['title' => 'Settings', 'active' => 'account'])

@section('content')
@php $s = $settings['settings'] ?? $settings; @endphp
<section class="acc-sub wrap" data-reveal>
    @include('site.account.partials.sub-hero', [
        'title' => 'App settings',
        'lede' => 'Alerts, language, and preferences — same as the Lens app.',
        'image' => asset('branding/account/settings.svg'),
        'icon' => 'gear',
        'tips' => ['Push & chat alerts', 'Booking reminders', 'Language preference'],
    ])

    <div class="acc-sub-body">
        <div class="acc-sub-card">
            <header class="acc-sub-card-head">
                <span class="acc-sub-card-ico" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 6v2M12 16v2M6 12h2M16 12h2M7.8 7.8l1.4 1.4M14.8 14.8l1.4 1.4M16.2 7.8l-1.4 1.4M9.2 14.8l-1.4 1.4"/><circle cx="12" cy="12" r="3"/></svg>
                </span>
                <div>
                    <h2>Notifications & language</h2>
                    <p>Control what Lens sends you and how it reads.</p>
                </div>
            </header>
            <form method="post" action="{{ route('site.account.settings.update') }}" class="acc-sub-form">
                @csrf
                <div class="acc-sub-toggles">
                    @foreach([
                        'push_enabled' => ['Push notifications', 'bell'],
                        'chat_alerts' => ['Chat alerts', 'chat'],
                        'booking_reminders' => ['Booking reminders', 'calendar'],
                        'review_prompts' => ['Review prompts', 'star'],
                        'offers' => ['Offers & promos', 'tag'],
                    ] as $key => [$label, $ico])
                        <label class="acc-sub-toggle">
                            <span class="acc-sub-toggle-ico" aria-hidden="true">
                                @switch($ico)
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
                            <span class="acc-sub-toggle-text">{{ $label }}</span>
                            <input type="hidden" name="{{ $key }}" value="0">
                            <input class="acc-sub-switch" type="checkbox" name="{{ $key }}" value="1" @checked(old($key, data_get($s, $key, true)))>
                        </label>
                    @endforeach
                </div>
                <label>
                    <span class="acc-sub-label"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 6h16M4 12h10M4 18h7"/></svg> Language</span>
                    <select name="locale">
                        <option value="en" @selected(old('locale', data_get($s, 'locale', 'en')) === 'en')>English</option>
                        <option value="ar" @selected(old('locale', data_get($s, 'locale')) === 'ar')>العربية</option>
                    </select>
                </label>
                <button class="ai-btn" type="submit">Save settings</button>
            </form>
            <div class="acc-sub-links">
                <a class="ghost-btn" href="{{ route('site.page', 'terms') }}">Terms</a>
                <a class="ghost-btn" href="{{ route('site.page', 'privacy') }}">Privacy</a>
            </div>
        </div>
    </div>
</section>
@endsection
