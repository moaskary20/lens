@extends('site.layout', ['title' => 'Book '.$vendor['display_name'], 'active' => 'bookings'])

@section('content')
@php
    $packages = collect($vendor['packages'] ?? []);
    $first = $packages->first();
@endphp
<section
    class="book-flow wrap"
    data-reveal
    x-data="bookFlow({
        vendorId: {{ (int) $vendor['id'] }},
        packageType: @js(old('package_type', $first['type'] ?? $first['key'] ?? 'half_day')),
        sessionPrice: @js(old('session_price', $first['price'] ?? $vendor['starting_from'] ?? 0)),
    })"
>
    <div class="page-head">
        <h1>Book {{ $vendor['display_name'] }}</h1>
        <p class="lede">{{ $vendor['profession'] ?? $vendor['vendor_type'] ?? 'Creative' }} · escrow-protected</p>
    </div>

    <div class="book-steps">
        <span :class="step === 1 && 'is-active'">1 · Package</span>
        <span :class="step === 2 && 'is-active'">2 · Project</span>
        <span :class="step === 3 && 'is-active'">3 · Schedule</span>
        <span :class="step === 4 && 'is-active'">4 · Pay</span>
        <span :class="step === 5 && 'is-active'">5 · Review</span>
    </div>

    <form method="post" action="{{ route('site.book.store') }}" class="site-shell-grid" @submit="busy = true">
        @csrf
        <input type="hidden" name="vendor_id" :value="vendorId">
        <input type="hidden" name="package_type" :value="packageType">
        <input type="hidden" name="session_price" :value="sessionPrice">

        <div class="site-shell-card">
            <div x-show="step === 1" x-cloak>
                <h2 style="margin-top:0">Choose a package</h2>
                @forelse($packages as $package)
                    @php
                        $type = $package['type'] ?? $package['key'] ?? $package['slug'] ?? 'custom';
                        $price = $package['price'] ?? $package['amount'] ?? 0;
                        $label = $package['label'] ?? $package['name'] ?? $type;
                    @endphp
                    <button type="button" class="package-option" :class="packageType === @js($type) && 'is-active'"
                            @click="selectPackage(@js($type), @js($price))">
                        <span><strong>{{ $label }}</strong><br><small class="lede">{{ $package['description'] ?? '' }}</small></span>
                        <span style="color:var(--orange);font-weight:800">{{ number_format((float) $price) }} EGP</span>
                    </button>
                @empty
                    <button type="button" class="package-option is-active" @click="selectPackage('half_day', {{ (float) ($vendor['starting_from'] ?? 0) }})">
                        <span><strong>Starting package</strong></span>
                        <span style="color:var(--orange);font-weight:800">{{ number_format((float) ($vendor['starting_from'] ?? 0)) }} EGP</span>
                    </button>
                @endforelse
                <button class="ai-btn" type="button" style="margin-top:12px" @click="next()" :disabled="!packageType">Continue</button>
            </div>

            <div class="site-shell-form" x-show="step === 2" x-cloak>
                <h2 style="margin-top:0">Project details</h2>
                <label><span>Project name</span><input name="project_name" x-model="projectName" value="{{ old('project_name') }}" required></label>
                <label><span>Project type</span><input name="project_type" x-model="projectType" value="{{ old('project_type', 'Photoshoot') }}" required></label>
                <label><span>Brief</span><textarea name="client_brief" x-model="clientBrief" required>{{ old('client_brief') }}</textarea></label>
                <label><span>Location</span><input name="location_text" x-model="locationText" value="{{ old('location_text') }}" required placeholder="Cairo · Zamalek"></label>
                <div class="cta-row">
                    <button class="ghost-btn" type="button" style="margin:0" @click="prev()">Back</button>
                    <button class="ai-btn" type="button" @click="next()">Continue</button>
                </div>
            </div>

            <div class="site-shell-form" x-show="step === 3" x-cloak>
                <h2 style="margin-top:0">Schedule</h2>
                <label><span>Date & time</span><input type="datetime-local" name="scheduled_at" x-model="scheduledAt" value="{{ old('scheduled_at') }}"></label>
                <label><span>Duration (hours)</span><input type="number" min="1" step="0.5" name="duration_hours" x-model="durationHours" value="{{ old('duration_hours', 2) }}"></label>
                <div class="cta-row">
                    <button class="ghost-btn" type="button" style="margin:0" @click="prev()">Back</button>
                    <button class="ai-btn" type="button" @click="next()">Continue</button>
                </div>
            </div>

            <div class="site-shell-form" x-show="step === 4" x-cloak>
                <h2 style="margin-top:0">Payment</h2>
                <label><span>Method</span>
                    <select name="payment_method" x-model="paymentMethod">
                        <option value="card">Card</option>
                        <option value="wallet">Mobile wallet</option>
                        <option value="paypal">PayPal</option>
                    </select>
                </label>
                <label><span>Promo code</span><input name="promo_code" x-model="promoCode" value="{{ old('promo_code') }}"></label>
                <div class="cta-row">
                    <button class="ghost-btn" type="button" style="margin:0" @click="prev()">Back</button>
                    <button class="ai-btn" type="button" @click="next()">Review</button>
                </div>
            </div>

            <div x-show="step === 5" x-cloak>
                <h2 style="margin-top:0">Confirm booking</h2>
                <p class="lede">Package <strong x-text="packageType"></strong> · <strong x-text="sessionPrice"></strong> EGP</p>
                <p class="lede" x-text="projectName"></p>
                <p class="lede" x-text="locationText"></p>
                @foreach($errors->all() as $error)<em class="bookings-error">{{ $error }}</em>@endforeach
                <div class="cta-row">
                    <button class="ghost-btn" type="button" style="margin:0" @click="prev()">Back</button>
                    <button class="ai-btn" type="submit" :disabled="busy">Pay & book</button>
                </div>
            </div>
        </div>

        <aside class="site-shell-card">
            <img src="{{ $vendor['cover_image'] ?? $vendor['profile_photo'] ?? asset('branding/lenslogo_mark.png') }}" alt="" style="border-radius:16px;aspect-ratio:16/10;object-fit:cover;width:100%">
            <h3>{{ $vendor['display_name'] }}</h3>
            <p class="lede">{{ $vendor['city'] ?? '' }} · ★ {{ number_format((float) ($vendor['rating_avg'] ?? 0), 1) }}</p>
            <p class="lede">Funds stay in escrow until you approve delivery.</p>
        </aside>
    </form>
</section>
@endsection
