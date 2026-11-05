@extends('site.layout', ['title' => $editing ? 'Creator studio' : 'Join as creator', 'active' => 'account'])

@section('content')
@php
    $p = $profile ?? [];
    $vendor = $p['vendor'] ?? $p;
@endphp
<section class="wizard-page wrap" data-reveal x-data="vendorWizard({ step: 1, total: 6 })">
    <div class="page-head">
        <h1>{{ $editing ? 'Edit creator profile' : 'Become a Lens creator' }}</h1>
        <p class="lede">Six steps — same verification flow as the mobile app.</p>
    </div>

    <div class="wizard-steps">
        @foreach(['Identity', 'Type & bio', 'Portfolio', 'Pricing', 'Calendar', 'Payouts'] as $i => $label)
            <span :class="{ 'is-active': step === {{ $i + 1 }}, 'is-done': step > {{ $i + 1 }} }">{{ $i + 1 }}. {{ $label }}</span>
        @endforeach
    </div>

    <form method="post"
          action="{{ $editing ? route('site.vendors.profile.update') : route('site.vendors.join.submit') }}"
          enctype="multipart/form-data"
          class="site-shell-card site-shell-form">
        @csrf
        @unless($editing)
            <input type="hidden" name="role" value="vendor">
        @endunless

        <div x-show="step === 1" x-cloak>
            <h2 style="margin-top:0">Identity</h2>
            <label><span>Full name</span><input name="name" value="{{ old('name', $p['name'] ?? '') }}" @required(!$editing)></label>
            <label><span>Email</span><input type="email" name="email" value="{{ old('email', $p['email'] ?? '') }}" @required(!$editing) @disabled($editing)></label>
            @unless($editing)
                <label><span>Password</span><input type="password" name="password" required></label>
            @endunless
            <label><span>Phone</span><input name="phone" value="{{ old('phone', $p['phone'] ?? '') }}" required></label>
            <label><span>Date of birth</span><input type="date" name="date_of_birth" value="{{ old('date_of_birth', $vendor['date_of_birth'] ?? '') }}" required></label>
            <label><span>Governorate</span>
                <select name="city_id" required>
                    <option value="">Choose…</option>
                    @foreach($cities as $city)
                        <option value="{{ $city->id }}" @selected(old('city_id', $p['city_id'] ?? $vendor['city_id'] ?? null) == $city->id)>{{ $city->name_en }}</option>
                    @endforeach
                </select>
            </label>
            <label><span>Profile photo</span><input type="file" name="profile_photo" accept="image/*"></label>
            <label><span>National ID image</span><input type="file" name="national_id_image" accept="image/*"></label>
            <button class="ai-btn" type="button" @click="next()">Continue</button>
        </div>

        <div x-show="step === 2" x-cloak>
            <h2 style="margin-top:0">Type & bio</h2>
            <label><span>Creator type</span>
                <select name="vendor_type" required>
                    @foreach($vendorTypes as $type)
                        <option value="{{ $type->slug }}" @selected(old('vendor_type', $vendor['vendor_type'] ?? $vendor['type'] ?? '') === $type->slug)>{{ $type->name_en }}</option>
                    @endforeach
                </select>
            </label>
            <label><span>Display name</span><input name="display_name" value="{{ old('display_name', $vendor['display_name'] ?? '') }}" required></label>
            <label><span>Profession</span><input name="profession" value="{{ old('profession', $vendor['profession'] ?? '') }}" required></label>
            <label><span>Years experience</span><input type="number" min="0" name="years_experience" value="{{ old('years_experience', $vendor['years_experience'] ?? 1) }}" required></label>
            <label><span>Bio</span><textarea name="bio" required>{{ old('bio', $vendor['bio'] ?? '') }}</textarea></label>
            <label><span>WhatsApp</span><input name="whatsapp" value="{{ old('whatsapp', $vendor['whatsapp'] ?? '') }}" required></label>
            <label><span>Contact email</span><input type="email" name="contact_email" value="{{ old('contact_email', $vendor['contact_email'] ?? $p['email'] ?? '') }}" required></label>
            <label><span>Instagram</span><input name="instagram" value="{{ old('instagram', $vendor['instagram'] ?? '') }}" required></label>
            <label><span>Instagram followers</span><input type="number" min="0" name="instagram_followers" value="{{ old('instagram_followers', $vendor['instagram_followers'] ?? 0) }}" required></label>
            <label><span>TikTok URL</span><input name="tiktok_url" value="{{ old('tiktok_url', $vendor['tiktok_url'] ?? '') }}" required></label>
            <label><span>TikTok followers</span><input type="number" min="0" name="tiktok_followers" value="{{ old('tiktok_followers', $vendor['tiktok_followers'] ?? 0) }}" required></label>
            <label><span>Facebook URL</span><input name="facebook_url" value="{{ old('facebook_url', $vendor['facebook_url'] ?? '') }}" required></label>
            <label><span>Facebook followers</span><input type="number" min="0" name="facebook_followers" value="{{ old('facebook_followers', $vendor['facebook_followers'] ?? 0) }}" required></label>
            <label><span>Address</span><input name="address" value="{{ old('address', $vendor['address'] ?? '') }}" required></label>
            <label><span>Latitude</span><input name="latitude" value="{{ old('latitude', $vendor['latitude'] ?? '30.0444') }}" required></label>
            <label><span>Longitude</span><input name="longitude" value="{{ old('longitude', $vendor['longitude'] ?? '31.2357') }}" required></label>
            <div class="cta-row">
                <button class="ghost-btn" type="button" style="margin:0" @click="prev()">Back</button>
                <button class="ai-btn" type="button" @click="next()">Continue</button>
            </div>
        </div>

        <div x-show="step === 3" x-cloak>
            <h2 style="margin-top:0">Portfolio</h2>
            <label><span>Cover image</span><input type="file" name="cover_image" accept="image/*"></label>
            <p class="lede">You can polish portfolio projects later from Creator studio / the app. Add at least your cover now.</p>
            <div class="cta-row">
                <button class="ghost-btn" type="button" style="margin:0" @click="prev()">Back</button>
                <button class="ai-btn" type="button" @click="next()">Continue</button>
            </div>
        </div>

        <div x-show="step === 4" x-cloak>
            <h2 style="margin-top:0">Pricing</h2>
            <label><span>Half day price</span><input type="number" min="0" name="half_day_price" value="{{ old('half_day_price', $vendor['half_day_price'] ?? '') }}"></label>
            <label><span>Full day price</span><input type="number" min="0" name="full_day_price" value="{{ old('full_day_price', $vendor['full_day_price'] ?? '') }}"></label>
            <label><span>Hourly price</span><input type="number" min="0" name="hourly_price" value="{{ old('hourly_price', $vendor['hourly_price'] ?? '') }}"></label>
            <label><span>Per video price</span><input type="number" min="0" name="per_video_price" value="{{ old('per_video_price', $vendor['per_video_price'] ?? '') }}"></label>
            <label><span>Turnaround hours</span><input type="number" min="1" name="turnaround_hours" value="{{ old('turnaround_hours', $vendor['turnaround_hours'] ?? 48) }}"></label>
            <div class="cta-row">
                <button class="ghost-btn" type="button" style="margin:0" @click="prev()">Back</button>
                <button class="ai-btn" type="button" @click="next()">Continue</button>
            </div>
        </div>

        <div x-show="step === 5" x-cloak>
            <h2 style="margin-top:0">Calendar</h2>
            <p class="lede">Default availability — refine later in Creator studio.</p>
            @foreach(['sun','mon','tue','wed','thu','fri','sat'] as $i => $day)
                <input type="hidden" name="availabilities[{{ $i }}][weekday]" value="{{ $i }}">
                <label class="sheet-check">
                    <input type="checkbox" name="availabilities[{{ $i }}][enabled]" value="1" checked>
                    <span>{{ strtoupper($day) }} 10:00–18:00</span>
                </label>
                <input type="hidden" name="availabilities[{{ $i }}][start_time]" value="10:00">
                <input type="hidden" name="availabilities[{{ $i }}][end_time]" value="18:00">
            @endforeach
            <div class="cta-row">
                <button class="ghost-btn" type="button" style="margin:0" @click="prev()">Back</button>
                <button class="ai-btn" type="button" @click="next()">Continue</button>
            </div>
        </div>

        <div x-show="step === 6" x-cloak>
            <h2 style="margin-top:0">Payouts</h2>
            <label><span>Payout method</span>
                <select name="payout_method" required>
                    <option value="bank" @selected(old('payout_method', $vendor['payout_method'] ?? '') === 'bank')>Bank</option>
                    <option value="wallet" @selected(old('payout_method', $vendor['payout_method'] ?? '') === 'wallet')>Wallet</option>
                    <option value="paypal" @selected(old('payout_method', $vendor['payout_method'] ?? '') === 'paypal')>PayPal</option>
                </select>
            </label>
            <label><span>Bank name</span><input name="bank_name" value="{{ old('bank_name', $vendor['bank_name'] ?? '') }}"></label>
            <label><span>Account holder</span><input name="bank_account_holder" value="{{ old('bank_account_holder', $vendor['bank_account_holder'] ?? '') }}"></label>
            <label><span>Account number</span><input name="bank_account_number" value="{{ old('bank_account_number', $vendor['bank_account_number'] ?? '') }}"></label>
            <label><span>Wallet phone</span><input name="wallet_phone" value="{{ old('wallet_phone', $vendor['wallet_phone'] ?? '') }}"></label>
            <label><span>PayPal email</span><input type="email" name="paypal_email" value="{{ old('paypal_email', $vendor['paypal_email'] ?? '') }}"></label>
            @foreach($errors->all() as $error)<em class="bookings-error">{{ $error }}</em>@endforeach
            <div class="cta-row">
                <button class="ghost-btn" type="button" style="margin:0" @click="prev()">Back</button>
                <button class="ai-btn" type="submit">{{ $editing ? 'Save profile' : 'Submit for verification' }}</button>
            </div>
        </div>
    </form>
</section>
@endsection
