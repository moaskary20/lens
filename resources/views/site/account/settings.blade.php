@extends('site.layout', ['title' => 'Settings', 'active' => 'account'])

@section('content')
@php $s = $settings['settings'] ?? $settings; @endphp
<section class="account-page wrap" data-reveal>
    <div class="site-shell-card" style="max-width:640px">
        <h1 style="margin-top:0">Notification settings</h1>
        <form method="post" action="{{ route('site.account.settings.update') }}" class="site-shell-form">
            @csrf
            @foreach([
                'push_enabled' => 'Push notifications',
                'chat_alerts' => 'Chat alerts',
                'booking_reminders' => 'Booking reminders',
                'review_prompts' => 'Review prompts',
                'offers' => 'Offers & promos',
            ] as $key => $label)
                <label class="sheet-check" style="margin:0">
                    <input type="hidden" name="{{ $key }}" value="0">
                    <input type="checkbox" name="{{ $key }}" value="1" @checked(old($key, data_get($s, $key, true)))>
                    <span>{{ $label }}</span>
                </label>
            @endforeach
            <label>
                <span>Language</span>
                <select name="locale">
                    <option value="en" @selected(old('locale', data_get($s, 'locale', 'en')) === 'en')>English</option>
                    <option value="ar" @selected(old('locale', data_get($s, 'locale')) === 'ar')>العربية</option>
                </select>
            </label>
            <button class="ai-btn" type="submit">Save settings</button>
        </form>
        <div class="cta-row" style="margin-top:18px">
            <a class="ghost-btn" href="{{ route('site.page', 'terms') }}" style="margin:0">Terms</a>
            <a class="ghost-btn" href="{{ route('site.page', 'privacy') }}" style="margin:0">Privacy</a>
        </div>
    </div>
</section>
@endsection
