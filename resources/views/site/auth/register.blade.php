@extends('site.layout', ['title' => 'Create account', 'active' => 'account'])

@section('content')
<section class="acc-sub wrap client-register" data-reveal>
    <header class="acc-sub-hero">
        <div class="acc-sub-hero-copy">
            <a class="acc-sub-back" href="{{ route('site.join') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg>
                Choose role
            </a>
            <div class="acc-sub-title-row">
                <span class="acc-sub-ico" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="3.2"/><path d="M5.5 19c1.8-3.4 4-5 6.5-5s4.7 1.6 6.5 5"/></svg>
                </span>
                <div>
                    <h1>Create your client account</h1>
                    <p>Book vendors across Egypt — same login on the website and the Lens app.</p>
                </div>
            </div>
            <ul class="acc-sub-tips">
                <li>Escrow-protected bookings</li>
                <li>Chat after you book</li>
                <li>One account everywhere</li>
            </ul>
        </div>
        <figure class="acc-sub-hero-art" aria-hidden="true">
            <img src="{{ asset('branding/account/join-client.svg') }}" alt="" loading="eager" decoding="async">
        </figure>
    </header>

    <div class="acc-sub-body">
        <div class="acc-sub-card">
            <header class="acc-sub-card-head">
                <span class="acc-sub-card-ico" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 5v14M5 12h14"/></svg>
                </span>
                <div>
                    <h2>Your details</h2>
                    <p>We’ll use this to verify bookings and send updates.</p>
                </div>
            </header>

            <form method="post" action="{{ route('site.register.submit') }}" class="acc-sub-form">
                @csrf
                <input type="hidden" name="role" value="client">

                <div class="acc-sub-grid">
                    <label>
                        <span class="acc-sub-label">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="8" r="3.2"/><path d="M5.5 19c1.8-3.4 4-5 6.5-5s4.7 1.6 6.5 5"/></svg>
                            Full name
                        </span>
                        <input name="name" value="{{ old('name') }}" required autocomplete="name">
                    </label>
                    <label>
                        <span class="acc-sub-label">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 6h16v12H4z"/><path d="m4 7 8 6 8-6"/></svg>
                            Email
                        </span>
                        <input type="email" name="email" value="{{ old('email') }}" required autocomplete="email">
                    </label>
                    <label>
                        <span class="acc-sub-label">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V8a4 4 0 0 1 8 0v2"/></svg>
                            Password
                        </span>
                        <input type="password" name="password" required autocomplete="new-password">
                    </label>
                    <label>
                        <span class="acc-sub-label">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M6.5 4.5h3l1.5 4-2 1.2a12 12 0 0 0 5.3 5.3l1.2-2 4 1.5v3A2 2 0 0 1 17.5 19.5 13 13 0 0 1 4.5 6.5a2 2 0 0 1 2-2z"/></svg>
                            Phone
                        </span>
                        <input name="phone" value="{{ old('phone') }}" required placeholder="01xxxxxxxxx" inputmode="numeric" autocomplete="tel">
                        <small class="client-register-hint">Egyptian mobile only — each number can be used once.</small>
                    </label>
                    <label>
                        <span class="acc-sub-label">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 21s6-5.2 6-10a6 6 0 1 0-12 0c0 4.8 6 10 6 10z"/><circle cx="12" cy="11" r="2.2"/></svg>
                            Governorate
                        </span>
                        <select name="city_id" required>
                            <option value="">Choose…</option>
                            @foreach($cities as $city)
                                <option value="{{ $city->id }}" @selected(old('city_id') == $city->id)>{{ $city->name_en }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        <span class="acc-sub-label">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 6h16M4 12h10M4 18h7"/></svg>
                            Language
                        </span>
                        <select name="locale">
                            <option value="en" @selected(old('locale', 'en') === 'en')>English</option>
                            <option value="ar" @selected(old('locale') === 'ar')>العربية</option>
                        </select>
                    </label>
                </div>

                @foreach($errors->all() as $error)
                    <em class="bookings-error">{{ $error }}</em>
                @endforeach

                <div class="client-register-actions">
                    <button class="ai-btn" type="submit">Create account</button>
                    <a class="ghost-btn" href="{{ route('site.login') }}">Already have an account?</a>
                </div>
            </form>
        </div>

        <div class="client-register-perks">
            <article class="client-register-perk">
                <span aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="7" width="18" height="12" rx="2"/><path d="M3 11h18M7 15h3"/></svg>
                </span>
                <div>
                    <strong>Pay into escrow</strong>
                    <p>Funds release when the job is done.</p>
                </div>
            </article>
            <article class="client-register-perk">
                <span aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 6.5A2.5 2.5 0 0 1 7.5 4h9A2.5 2.5 0 0 1 19 6.5v6A2.5 2.5 0 0 1 16.5 15H11l-4 3v-3H7.5A2.5 2.5 0 0 1 5 12.5z"/></svg>
                </span>
                <div>
                    <strong>Chat with vendors</strong>
                    <p>Message after booking is confirmed.</p>
                </div>
            </article>
            <article class="client-register-perk">
                <span aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="6"/><path d="m16 16 4 4"/></svg>
                </span>
                <div>
                    <strong>Find the right fit</strong>
                    <p>Photographers, studios, models, and more.</p>
                </div>
            </article>
        </div>
    </div>
</section>
@endsection
