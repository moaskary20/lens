@extends('site.layout', ['title' => 'Edit profile', 'active' => 'account'])

@section('content')
<section class="acc-sub wrap" data-reveal>
    @include('site.account.partials.sub-hero', [
        'title' => 'Edit profile',
        'lede' => 'Update your name, phone, and location — same profile as the app.',
        'image' => asset('branding/account/profile.svg'),
        'icon' => 'profile',
        'tips' => ['Visible on bookings', 'Synced with the app', 'Optional phone for vendors'],
    ])

    <div class="acc-sub-body">
        <div class="acc-sub-card">
            <header class="acc-sub-card-head">
                <span class="acc-sub-card-ico" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="3.2"/><path d="M5.5 19c1.8-3.4 4-5 6.5-5s4.7 1.6 6.5 5"/></svg>
                </span>
                <div>
                    <h2>Profile details</h2>
                    <p>Keep your contact info current for smoother bookings.</p>
                </div>
            </header>
            <form method="post" action="{{ route('site.account.update') }}" class="acc-sub-form" enctype="multipart/form-data">
                @csrf
                <label>
                    <span class="acc-sub-label"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 20v-1.2A5.2 5.2 0 0 1 9.2 13.6h5.6A5.2 5.2 0 0 1 20 18.8V20"/><circle cx="12" cy="7.5" r="3.2"/></svg> Name</span>
                    <input name="name" value="{{ old('name', $profile['name'] ?? '') }}" required>
                </label>
                <label>
                    <span class="acc-sub-label"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M6.5 4.5h3l1.5 4-2 1.2a12 12 0 0 0 5.3 5.3l1.2-2 4 1.5v3A2 2 0 0 1 17.5 19.5 13 13 0 0 1 4.5 6.5a2 2 0 0 1 2-2z"/></svg> Phone</span>
                    <input name="phone" value="{{ old('phone', $profile['phone'] ?? '') }}" placeholder="+20…">
                </label>
                <div class="acc-sub-grid">
                    <label>
                        <span class="acc-sub-label"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 21s6-5.2 6-10a6 6 0 1 0-12 0c0 4.8 6 10 6 10z"/><circle cx="12" cy="11" r="2.2"/></svg> Governorate</span>
                        <select name="city_id">
                            <option value="">Choose…</option>
                            @foreach($cities as $city)
                                <option value="{{ $city->id }}" @selected(old('city_id', $profile['city_id'] ?? null) == $city->id)>{{ $city->name_en }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        <span class="acc-sub-label"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 6h16M4 12h10M4 18h7"/></svg> Language</span>
                        <select name="locale">
                            <option value="en" @selected(old('locale', $profile['locale'] ?? 'en') === 'en')>English</option>
                            <option value="ar" @selected(old('locale', $profile['locale'] ?? '') === 'ar')>العربية</option>
                        </select>
                    </label>
                </div>
                @foreach($errors->all() as $error)<em class="bookings-error">{{ $error }}</em>@endforeach
                <button class="ai-btn" type="submit">Save profile</button>
            </form>
        </div>
    </div>
</section>
@endsection
