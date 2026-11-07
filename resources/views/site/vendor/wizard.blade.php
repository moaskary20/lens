@extends('site.layout', ['title' => $editing ? 'Vendor studio' : 'Join as a vendor', 'active' => 'account'])

@section('content')
@php
    $p = $profile ?? [];
    $vendor = $p['vendor'] ?? $p;
    $steps = [
        ['label' => 'Identity', 'hint' => 'Who you are'],
        ['label' => 'Type & bio', 'hint' => 'Your craft'],
        ['label' => 'Portfolio', 'hint' => 'Your work'],
        ['label' => 'Pricing', 'hint' => 'Your rates'],
        ['label' => 'Calendar', 'hint' => 'Availability'],
        ['label' => 'Payouts', 'hint' => 'Get paid'],
    ];
@endphp
<section class="acc-sub wrap vendor-join" data-reveal x-data="vendorWizard({ step: 1, total: 6 })">
    <header class="acc-sub-hero">
        <div class="acc-sub-hero-copy">
            @if($editing)
                <a class="acc-sub-back" href="{{ route('site.account') }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg>
                    Account
                </a>
            @else
                <a class="acc-sub-back" href="{{ route('site.join') }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg>
                    Choose role
                </a>
            @endif
            <div class="acc-sub-title-row">
                <span class="acc-sub-ico" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="14" rx="3"/><circle cx="12" cy="12" r="3.2"/><path d="M8 5V3m8 2V3"/></svg>
                </span>
                <div>
                    <h1>{{ $editing ? 'Edit vendor profile' : 'Become a Lens vendor' }}</h1>
                    <p>{{ $editing ? 'Update your studio details — same profile as the app.' : 'Six steps to publish on Lens — same verification flow as the mobile app.' }}</p>
                </div>
            </div>
            <ul class="acc-sub-tips">
                <li>Escrow-backed bookings</li>
                <li>Egypt-wide discovery</li>
                <li>Same login on web &amp; app</li>
            </ul>
        </div>
        <figure class="acc-sub-hero-art" aria-hidden="true">
            <img src="{{ asset('branding/account/join-vendor.svg') }}" alt="" loading="eager" decoding="async">
        </figure>
    </header>

    <div class="acc-sub-body">
        <nav class="vj-steps" aria-label="Onboarding steps">
            @foreach($steps as $i => $step)
                <button
                    type="button"
                    class="vj-step"
                    :class="{ 'is-active': step === {{ $i + 1 }}, 'is-done': step > {{ $i + 1 }} }"
                    @click="step = {{ $i + 1 }}"
                >
                    <span class="vj-step-num" aria-hidden="true">
                        <span x-show="step <= {{ $i + 1 }}">{{ $i + 1 }}</span>
                        <svg x-cloak x-show="step > {{ $i + 1 }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="m5 12 5 5L20 7"/></svg>
                    </span>
                    <span class="vj-step-copy">
                        <strong>{{ $step['label'] }}</strong>
                        <small>{{ $step['hint'] }}</small>
                    </span>
                </button>
            @endforeach
        </nav>

        <form method="post"
              action="{{ $editing ? route('site.vendors.profile.update') : route('site.vendors.join.submit') }}"
              enctype="multipart/form-data"
              class="acc-sub-card vj-card acc-sub-form">
            @csrf
            @unless($editing)
                <input type="hidden" name="role" value="vendor">
            @endunless

            <div x-show="step === 1" x-cloak class="vj-pane">
                <header class="acc-sub-card-head">
                    <span class="acc-sub-card-ico" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="3.2"/><path d="M5.5 19c1.8-3.4 4-5 6.5-5s4.7 1.6 6.5 5"/></svg>
                    </span>
                    <div>
                        <h2>Identity</h2>
                        <p>Legal name, contact, and verification photos.</p>
                    </div>
                </header>
                <div class="acc-sub-grid">
                    <label><span class="acc-sub-label"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="8" r="3.2"/><path d="M5.5 19c1.8-3.4 4-5 6.5-5s4.7 1.6 6.5 5"/></svg> Full name</span><input name="name" value="{{ old('name', $p['name'] ?? '') }}" @required(!$editing)></label>
                    <label><span class="acc-sub-label"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 6h16v12H4z"/><path d="m4 7 8 6 8-6"/></svg> Email</span><input type="email" name="email" value="{{ old('email', $p['email'] ?? '') }}" @required(!$editing) @disabled($editing)></label>
                    @unless($editing)
                        <label><span class="acc-sub-label"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V8a4 4 0 0 1 8 0v2"/></svg> Password</span><input type="password" name="password" required></label>
                    @endunless
                    <label><span class="acc-sub-label"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M6.5 4.5h3l1.5 4-2 1.2a12 12 0 0 0 5.3 5.3l1.2-2 4 1.5v3A2 2 0 0 1 17.5 19.5 13 13 0 0 1 4.5 6.5a2 2 0 0 1 2-2z"/></svg> Phone</span><input name="phone" value="{{ old('phone', $p['phone'] ?? '') }}" required placeholder="01xxxxxxxxx" inputmode="numeric"></label>
                    <label><span class="acc-sub-label"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="4" y="5" width="16" height="15" rx="2"/><path d="M8 3v4M16 3v4M4 10h16"/></svg> Date of birth</span><input type="date" name="date_of_birth" value="{{ old('date_of_birth', $vendor['date_of_birth'] ?? '') }}" required></label>
                    <label><span class="acc-sub-label"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 21s6-5.2 6-10a6 6 0 1 0-12 0c0 4.8 6 10 6 10z"/><circle cx="12" cy="11" r="2.2"/></svg> Governorate</span>
                        <select name="city_id" required>
                            <option value="">Choose…</option>
                            @foreach($cities as $city)
                                <option value="{{ $city->id }}" @selected(old('city_id', $p['city_id'] ?? $vendor['city_id'] ?? null) == $city->id)>{{ $city->name_en }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label><span class="acc-sub-label"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="4" y="5" width="16" height="14" rx="2"/><circle cx="12" cy="12" r="3"/></svg> Profile photo</span><input type="file" name="profile_photo" accept="image/*"></label>
                    <label><span class="acc-sub-label"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M7 15h10M9 9h.01M15 9h.01"/></svg> National ID image</span><input type="file" name="national_id_image" accept="image/*"></label>
                </div>
                <div class="vj-actions">
                    <button class="ai-btn" type="button" @click="next()">Continue</button>
                </div>
            </div>

            <div x-show="step === 2" x-cloak class="vj-pane">
                <header class="acc-sub-card-head">
                    <span class="acc-sub-card-ico" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 7h16M4 12h10M4 17h14"/></svg>
                    </span>
                    <div>
                        <h2>Type &amp; bio</h2>
                        <p>How clients discover you on Lens.</p>
                    </div>
                </header>
                <div class="acc-sub-grid">
                    <label><span class="acc-sub-label">Vendor type</span>
                        <select name="vendor_type" required>
                            @foreach($vendorTypes as $type)
                                <option value="{{ $type->slug }}" @selected(old('vendor_type', $vendor['vendor_type'] ?? $vendor['type'] ?? '') === $type->slug)>{{ $type->name_en }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label><span class="acc-sub-label">Display name</span><input name="display_name" value="{{ old('display_name', $vendor['display_name'] ?? '') }}" required></label>
                    <label><span class="acc-sub-label">Profession</span><input name="profession" value="{{ old('profession', $vendor['profession'] ?? '') }}" required></label>
                    <label><span class="acc-sub-label">Years experience</span><input type="number" min="0" name="years_experience" value="{{ old('years_experience', $vendor['years_experience'] ?? 1) }}" required></label>
                    <label class="acc-sub-span-2"><span class="acc-sub-label">Bio</span><textarea name="bio" required>{{ old('bio', $vendor['bio'] ?? '') }}</textarea></label>
                    <label><span class="acc-sub-label">WhatsApp</span><input name="whatsapp" value="{{ old('whatsapp', $vendor['whatsapp'] ?? '') }}" required placeholder="01xxxxxxxxx"></label>
                    <label><span class="acc-sub-label">Contact email</span><input type="email" name="contact_email" value="{{ old('contact_email', $vendor['contact_email'] ?? $p['email'] ?? '') }}" required></label>
                    <label><span class="acc-sub-label">Instagram</span><input name="instagram" value="{{ old('instagram', $vendor['instagram'] ?? '') }}" required></label>
                    <label><span class="acc-sub-label">Instagram followers</span><input type="number" min="0" name="instagram_followers" value="{{ old('instagram_followers', $vendor['instagram_followers'] ?? 0) }}" required></label>
                    <label><span class="acc-sub-label">TikTok URL</span><input name="tiktok_url" value="{{ old('tiktok_url', $vendor['tiktok_url'] ?? '') }}" required></label>
                    <label><span class="acc-sub-label">TikTok followers</span><input type="number" min="0" name="tiktok_followers" value="{{ old('tiktok_followers', $vendor['tiktok_followers'] ?? 0) }}" required></label>
                    <label><span class="acc-sub-label">Facebook URL</span><input name="facebook_url" value="{{ old('facebook_url', $vendor['facebook_url'] ?? '') }}" required></label>
                    <label><span class="acc-sub-label">Facebook followers</span><input type="number" min="0" name="facebook_followers" value="{{ old('facebook_followers', $vendor['facebook_followers'] ?? 0) }}" required></label>
                    <label class="acc-sub-span-2"><span class="acc-sub-label">Address</span><input name="address" value="{{ old('address', $vendor['address'] ?? '') }}" required></label>
                    <label><span class="acc-sub-label">Latitude</span><input name="latitude" value="{{ old('latitude', $vendor['latitude'] ?? '30.0444') }}" required></label>
                    <label><span class="acc-sub-label">Longitude</span><input name="longitude" value="{{ old('longitude', $vendor['longitude'] ?? '31.2357') }}" required></label>
                </div>
                <div class="vj-actions">
                    <button class="ghost-btn" type="button" @click="prev()">Back</button>
                    <button class="ai-btn" type="button" @click="next()">Continue</button>
                </div>
            </div>

            <div x-show="step === 3" x-cloak class="vj-pane">
                <header class="acc-sub-card-head">
                    <span class="acc-sub-card-ico" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="11" r="2"/><path d="m21 16-5-4-4 3-3-2-4 4"/></svg>
                    </span>
                    <div>
                        <h2>Portfolio</h2>
                        <p>Add a cover — polish projects later in Vendor studio.</p>
                    </div>
                </header>
                <div class="vj-upload">
                    <span class="vj-upload-ico" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 16V6m0 0 4 4m-4-4-4 4"/><path d="M4 18h16"/></svg>
                    </span>
                    <div>
                        <strong>Cover image</strong>
                        <p>Landscape works best. JPG or PNG.</p>
                    </div>
                    <input type="file" name="cover_image" accept="image/*">
                </div>
                <div class="vj-actions">
                    <button class="ghost-btn" type="button" @click="prev()">Back</button>
                    <button class="ai-btn" type="button" @click="next()">Continue</button>
                </div>
            </div>

            <div x-show="step === 4" x-cloak class="vj-pane">
                <header class="acc-sub-card-head">
                    <span class="acc-sub-card-ico" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="6" width="18" height="12" rx="2.5"/><path d="M3 10h18M7 15h4"/></svg>
                    </span>
                    <div>
                        <h2>Pricing</h2>
                        <p>Set the rates clients see when they book you.</p>
                    </div>
                </header>
                <div class="acc-sub-grid">
                    <label><span class="acc-sub-label">Half day price</span><input type="number" min="0" name="half_day_price" value="{{ old('half_day_price', $vendor['half_day_price'] ?? '') }}" placeholder="EGP"></label>
                    <label><span class="acc-sub-label">Full day price</span><input type="number" min="0" name="full_day_price" value="{{ old('full_day_price', $vendor['full_day_price'] ?? '') }}" placeholder="EGP"></label>
                    <label><span class="acc-sub-label">Hourly price</span><input type="number" min="0" name="hourly_price" value="{{ old('hourly_price', $vendor['hourly_price'] ?? '') }}" placeholder="EGP"></label>
                    <label><span class="acc-sub-label">Per video price</span><input type="number" min="0" name="per_video_price" value="{{ old('per_video_price', $vendor['per_video_price'] ?? '') }}" placeholder="EGP"></label>
                    <label><span class="acc-sub-label">Turnaround hours</span><input type="number" min="1" name="turnaround_hours" value="{{ old('turnaround_hours', $vendor['turnaround_hours'] ?? 48) }}"></label>
                </div>
                <div class="vj-actions">
                    <button class="ghost-btn" type="button" @click="prev()">Back</button>
                    <button class="ai-btn" type="button" @click="next()">Continue</button>
                </div>
            </div>

            <div
                x-show="step === 5"
                x-cloak
                class="vj-pane"
                x-data="{
                    days: [
                        { key: 'Sun', full: 'Sunday', on: true, start: '10:00', end: '18:00' },
                        { key: 'Mon', full: 'Monday', on: true, start: '10:00', end: '18:00' },
                        { key: 'Tue', full: 'Tuesday', on: true, start: '10:00', end: '18:00' },
                        { key: 'Wed', full: 'Wednesday', on: true, start: '10:00', end: '18:00' },
                        { key: 'Thu', full: 'Thursday', on: true, start: '10:00', end: '18:00' },
                        { key: 'Fri', full: 'Friday', on: false, start: '10:00', end: '18:00' },
                        { key: 'Sat', full: 'Saturday', on: true, start: '10:00', end: '18:00' },
                    ],
                    get activeCount() { return this.days.filter(d => d.on).length; },
                    setWeekdays() {
                        this.days.forEach((d, i) => { d.on = i >= 1 && i <= 5; });
                    },
                    setAll(on) {
                        this.days.forEach(d => { d.on = on; });
                    },
                    slotIso(weekday, time, weeksAhead = 0) {
                        const now = new Date();
                        const date = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 0, 0, 0, 0);
                        const delta = (weekday - date.getDay() + 7) % 7;
                        date.setDate(date.getDate() + delta + (weeksAhead * 7));
                        const [hh, mm] = String(time || '10:00').split(':');
                        date.setHours(Number(hh) || 0, Number(mm) || 0, 0, 0);
                        return date.toISOString();
                    },
                    payloadSlots() {
                        const out = [];
                        this.days.forEach((day, weekday) => {
                            if (!day.on) return;
                            out.push({
                                starts_at: this.slotIso(weekday, day.start, 0),
                                ends_at: this.slotIso(weekday, day.end, 0),
                                status: 'open',
                            });
                        });
                        return out;
                    },
                }"
            >
                <header class="acc-sub-card-head">
                    <span class="acc-sub-card-ico" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="4" y="5" width="16" height="15" rx="2"/><path d="M8 3v4M16 3v4M4 10h16"/></svg>
                    </span>
                    <div>
                        <h2>Calendar</h2>
                        <p>Pick your open days and hours — you can refine later in Vendor studio.</p>
                    </div>
                </header>

                <div class="vj-cal">
                    <div class="vj-cal-toolbar">
                        <div class="vj-cal-summary">
                            <span class="vj-cal-summary-ico" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 7v5l3 2"/><circle cx="12" cy="12" r="8"/></svg>
                            </span>
                            <div>
                                <strong x-text="activeCount + ' day' + (activeCount === 1 ? '' : 's') + ' open this week'"></strong>
                                <small>Tap a day to toggle · edit hours when open</small>
                            </div>
                        </div>
                        <div class="vj-cal-presets">
                            <button type="button" class="ghost-btn" @click="setWeekdays()">Weekdays</button>
                            <button type="button" class="ghost-btn" @click="setAll(true)">All days</button>
                            <button type="button" class="ghost-btn" @click="setAll(false)">Clear</button>
                        </div>
                    </div>

                    <div class="vj-days" role="group" aria-label="Weekly availability">
                        <template x-for="(day, i) in days" :key="day.key">
                            <div class="vj-day" :class="{ 'is-on': day.on }">
                                <button type="button" class="vj-day-card" @click="day.on = !day.on" :aria-pressed="day.on.toString()" :aria-label="day.full">
                                    <span class="vj-day-badge" x-text="day.key"></span>
                                    <strong x-text="day.full"></strong>
                                    <span class="vj-day-meta">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 7v5l3 2"/><circle cx="12" cy="12" r="8"/></svg>
                                        <small x-show="day.on" x-text="day.start + ' – ' + day.end"></small>
                                        <small x-show="!day.on" class="is-off">Day off</small>
                                    </span>
                                    <span class="vj-day-check" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="m5 12 5 5L20 7"/></svg>
                                    </span>
                                </button>

                                <div class="vj-day-hours" x-show="day.on" x-cloak @click.stop>
                                    <label>
                                        <span>From</span>
                                        <input type="time" x-model="day.start">
                                    </label>
                                    <span class="vj-day-hours-sep" aria-hidden="true">→</span>
                                    <label>
                                        <span>To</span>
                                        <input type="time" x-model="day.end">
                                    </label>
                                </div>
                            </div>
                        </template>
                    </div>

                    <template x-for="(slot, index) in payloadSlots()" :key="'slot-' + index">
                        <div hidden>
                            <input type="hidden" :name="'availabilities[' + index + '][starts_at]'" :value="slot.starts_at">
                            <input type="hidden" :name="'availabilities[' + index + '][ends_at]'" :value="slot.ends_at">
                            <input type="hidden" :name="'availabilities[' + index + '][status]'" :value="slot.status">
                        </div>
                    </template>
                </div>

                <div class="vj-actions">
                    <button class="ghost-btn" type="button" @click="prev()">Back</button>
                    <button class="ai-btn" type="button" @click="next()">Continue</button>
                </div>
            </div>

            <div x-show="step === 6" x-cloak class="vj-pane">
                <header class="acc-sub-card-head">
                    <span class="acc-sub-card-ico" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="7" width="18" height="12" rx="2"/><path d="M3 11h18M7 15h3"/></svg>
                    </span>
                    <div>
                        <h2>Payouts</h2>
                        <p>Where Lens sends your earnings after escrow release.</p>
                    </div>
                </header>
                <div class="acc-sub-grid">
                    <label><span class="acc-sub-label">Payout method</span>
                        <select name="payout_method" required>
                            <option value="bank" @selected(old('payout_method', $vendor['payout_method'] ?? '') === 'bank')>Bank</option>
                            <option value="wallet" @selected(old('payout_method', $vendor['payout_method'] ?? '') === 'wallet')>Wallet</option>
                            <option value="paypal" @selected(old('payout_method', $vendor['payout_method'] ?? '') === 'paypal')>PayPal</option>
                        </select>
                    </label>
                    <label><span class="acc-sub-label">Bank name</span><input name="bank_name" value="{{ old('bank_name', $vendor['bank_name'] ?? '') }}"></label>
                    <label><span class="acc-sub-label">Account holder</span><input name="bank_account_holder" value="{{ old('bank_account_holder', $vendor['bank_account_holder'] ?? '') }}"></label>
                    <label><span class="acc-sub-label">Account number</span><input name="bank_account_number" value="{{ old('bank_account_number', $vendor['bank_account_number'] ?? '') }}"></label>
                    <label><span class="acc-sub-label">Wallet phone</span><input name="wallet_phone" value="{{ old('wallet_phone', $vendor['wallet_phone'] ?? '') }}" placeholder="01xxxxxxxxx"></label>
                    <label><span class="acc-sub-label">PayPal email</span><input type="email" name="paypal_email" value="{{ old('paypal_email', $vendor['paypal_email'] ?? '') }}"></label>
                </div>
                @foreach($errors->all() as $error)<em class="bookings-error">{{ $error }}</em>@endforeach
                <div class="vj-actions">
                    <button class="ghost-btn" type="button" @click="prev()">Back</button>
                    <button class="ai-btn" type="submit">{{ $editing ? 'Save profile' : 'Submit for verification' }}</button>
                </div>
            </div>
        </form>
    </div>
</section>
@endsection
