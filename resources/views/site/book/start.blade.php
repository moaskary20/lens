@extends('site.layout', ['title' => 'Book '.$vendor['display_name'], 'active' => 'bookings'])

@php
    $packages = collect($vendor['packages'] ?? [])->values();
    $first = $packages->first();
    $vendorType = $vendorType ?? ($vendor['vendor_type_slug'] ?? $vendor['vendor_type'] ?? '');
    $typeLabel = $typeLabel ?? 'Project type';
    $typeOptions = $typeOptions ?? ['Brand Campaign', 'Product', 'Editorial', 'Social Content', 'Event', 'Personal'];
    $extraFields = $extraFields ?? [];
    $timeSlots = $timeSlots ?? ['10:00 AM', '12:00 PM', '2:00 PM', '4:00 PM', '6:00 PM'];
    $bookPhoto = $vendor['cover_url'] ?? $vendor['cover_image'] ?? $vendor['profile_photo_url'] ?? $vendor['profile_photo'] ?? null;
    $minDate = now()->format('Y-m-d');
@endphp

@section('content')
<section
    class="book-flow wrap"
    data-reveal
    x-data="bookFlow({
        vendorId: {{ (int) $vendor['id'] }},
        quoteUrl: @js($quoteUrl ?? route('site.book.quote')),
        csrf: @js(csrf_token()),
        packages: @js($packages->all()),
        timeSlots: @js($timeSlots),
        typeLabel: @js($typeLabel),
        extraFields: @js($extraFields),
        packageType: @js(old('package_type', $first['key'] ?? $first['type'] ?? '')),
        packageLabel: @js(old('package_label', $first['label'] ?? $first['name'] ?? '')),
        sessionPrice: @js((float) old('session_price', $first['price'] ?? $vendor['starting_from'] ?? 0)),
        durationHours: @js(old('duration_hours', $first['duration_hours'] ?? null)),
        dateValue: @js(old('date_value', $minDate)),
        timeValue: @js(old('time_value', '10:00 AM')),
        projectName: @js(old('project_name', '')),
        projectType: @js(old('project_type', '')),
        clientBrief: @js(old('client_brief', '')),
        notes: @js(old('notes', '')),
        locationText: @js(old('location_text', '')),
        locationLat: @js(old('location_lat', '')),
        locationLng: @js(old('location_lng', '')),
        details: @js(old('project_details', [])),
        paymentMethod: @js(old('payment_method', 'card')),
        promoCode: @js(old('promo_code', '')),
    })"
>
    <div class="page-head">
        <h1>Book {{ $vendor['display_name'] }}</h1>
        <p class="lede">{{ $vendor['profession'] ?? $vendor['vendor_type'] ?? 'Creative' }} · same steps as the Lens app · escrow-protected</p>
    </div>

    <div class="book-steps">
        <span :class="step === 1 && 'is-active'">1 · Date &amp; time</span>
        <span :class="step === 2 && 'is-active'">2 · Project</span>
        <span :class="step === 3 && 'is-active'">3 · Review</span>
        <span :class="step === 4 && 'is-active'">4 · Payment</span>
    </div>

    <form method="post" action="{{ route('site.book.store') }}" class="site-shell-grid" @submit="beforeSubmit($event)">
        @csrf
        <input type="hidden" name="vendor_id" :value="vendorId">
        <input type="hidden" name="package_type" :value="packageType">
        <input type="hidden" name="session_price" :value="sessionPrice">
        <input type="hidden" name="scheduled_at" :value="scheduledAt">
        <input type="hidden" name="duration_hours" :value="durationHours || ''">
        <input type="hidden" name="location_lat" :value="locationLat || ''">
        <input type="hidden" name="location_lng" :value="locationLng || ''">
        <input type="hidden" name="payment_method" :value="paymentMethod">
        <input type="hidden" name="payment_details[method]" :value="paymentMethod">
        <input type="hidden" name="payment_details[card_holder]" :value="payment.card_holder">
        <input type="hidden" name="payment_details[card_brand]" :value="payment.card_brand">
        <input type="hidden" name="payment_details[card_last4]" :value="payment.card_last4 || payment.card_number">
        <input type="hidden" name="payment_details[card_expiry]" :value="payment.card_expiry">
        <input type="hidden" name="payment_details[wallet_phone]" :value="payment.wallet_phone">
        <input type="hidden" name="payment_details[wallet_telecom]" :value="payment.wallet_telecom">
        <input type="hidden" name="payment_details[paypal_email]" :value="payment.paypal_email">
        <input type="hidden" name="payment_details[paypal_name]" :value="payment.paypal_name">
        <template x-for="field in extraFields" :key="field.key">
            <input type="hidden" :name="'project_details[' + field.key + ']'" :value="details[field.key] || ''">
        </template>

        <div class="site-shell-card book-main">
            <p class="book-error" x-show="error" x-text="error" x-cloak></p>
            @foreach($errors->all() as $error)
                <em class="bookings-error">{{ $error }}</em>
            @endforeach

            {{-- Step 1: Date & time (mobile BookDatePage) --}}
            <div x-show="step === 1" x-cloak>
                <h2 style="margin-top:0">Choose your date</h2>
                <p class="lede">Pick a day and start time — same slots as the Lens app.</p>

                <label class="book-field">
                    <span>Date *</span>
                    <input type="date" :min="@js($minDate)" :value="dateValue" @change="setDate($event.target.value)" required>
                </label>

                <p class="book-label">Available time *</p>
                <div class="book-chip-row">
                    <template x-for="slot in timeSlots" :key="slot">
                        <button type="button" class="book-chip" :class="timeValue === slot && 'is-active'" @click="setTime(slot)" x-text="slot"></button>
                    </template>
                </div>

                <div class="cta-row">
                    <button class="ai-btn" type="button" @click="next()">Continue</button>
                </div>
            </div>

            {{-- Step 2: Project (mobile BookProjectPage) --}}
            <div class="site-shell-form" x-show="step === 2" x-cloak>
                <h2 style="margin-top:0">Tell them about your project</h2>
                <p class="lede">Fill the brief, choose a price, and pin the location — required fields match the app.</p>

                <label class="book-field">
                    <span>Project name *</span>
                    <input name="project_name" x-model="projectName" placeholder="E.g. Summer Menu Campaign" required>
                </label>

                <label class="book-field">
                    <span>{{ $typeLabel }} *</span>
                    <select name="project_type" x-model="projectType" required>
                        <option value="">Select {{ strtolower($typeLabel) }}</option>
                        @foreach($typeOptions as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                </label>

                @foreach($extraFields as $field)
                    <label class="book-field">
                        <span>{{ $field['label'] }}{{ ! empty($field['required']) ? ' *' : '' }}</span>
                        <select x-model="details['{{ $field['key'] }}']" @if(! empty($field['required'])) required @endif>
                            <option value="">Select {{ strtolower($field['label']) }}</option>
                            @foreach($field['options'] as $option)
                                <option value="{{ $option }}">{{ $option }}</option>
                            @endforeach
                        </select>
                    </label>
                @endforeach

                <p class="book-label">Session price *</p>
                <div class="book-packages">
                    @forelse($packages as $package)
                        @php
                            $key = $package['key'] ?? $package['type'] ?? 'custom';
                            $price = (float) ($package['price'] ?? 0);
                            $label = $package['label'] ?? $package['name'] ?? $key;
                            $hours = $package['duration_hours'] ?? null;
                        @endphp
                        <button type="button" class="package-option" :class="packageType === @js($key) && 'is-active'"
                                @click="selectPackage(@js(['key' => $key, 'label' => $label, 'price' => $price, 'duration_hours' => $hours]))">
                            <span>
                                <strong>{{ $label }}</strong>
                                @if($hours)
                                    <br><small class="lede">{{ $hours }} hours</small>
                                @endif
                            </span>
                            <span style="color:var(--orange);font-weight:800">{{ number_format($price) }} EGP</span>
                        </button>
                    @empty
                        <p class="lede">No bookable packages yet for this creator.</p>
                    @endforelse
                </div>

                <p class="book-label">Location *</p>
                <input type="hidden" name="location_text" :value="locationText">
                <button type="button" class="book-map-trigger" @click="openMapPicker()">
                    <span class="book-map-trigger-ico" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path d="M12 21s7-5.4 7-11a7 7 0 1 0-14 0c0 5.6 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>
                    </span>
                    <span class="book-map-trigger-copy">
                        <strong x-text="locationText || 'Pin on the map'"></strong>
                        <small x-show="locationLat && locationLng" x-cloak x-text="`${Number(locationLat).toFixed(5)}, ${Number(locationLng).toFixed(5)}`"></small>
                        <small x-show="!(locationLat && locationLng)">Same map picker as the Lens app — search, drop a pin, or use GPS</small>
                    </span>
                    <span class="book-map-trigger-cta">Open map</span>
                </button>

                <label class="book-field">
                    <span>Short brief *</span>
                    <textarea name="client_brief" x-model="clientBrief" maxlength="500" rows="5" placeholder="Tell us about your project, goals, and what you have in mind..." required></textarea>
                    <small class="lede"><span x-text="(clientBrief || '').length"></span>/500</small>
                </label>

                <label class="book-field">
                    <span>Additional notes (optional)</span>
                    <textarea name="notes" x-model="notes" rows="3" placeholder="Any other details, special requests, or important notes..."></textarea>
                </label>

                <div class="cta-row">
                    <button class="ghost-btn" type="button" style="margin:0" @click="prev()">Back</button>
                    <button class="ai-btn" type="button" @click="next()">Review booking</button>
                </div>
            </div>

            {{-- Step 3: Review (mobile BookReviewPage) --}}
            <div x-show="step === 3" x-cloak>
                <h2 style="margin-top:0">Review your booking</h2>
                <p class="lede">Check the details below, then continue to payment.</p>

                <div class="book-review-card">
                    <h3>Schedule</h3>
                    <p><strong x-text="dateValue"></strong> · <span x-text="timeValue"></span></p>
                    <p class="lede" x-show="durationHours">Duration: <span x-text="durationHours"></span> hours</p>
                </div>
                <div class="book-review-card">
                    <h3>Location</h3>
                    <p x-text="locationText"></p>
                    <p class="lede" x-show="locationLat && locationLng" x-text="`${locationLat}, ${locationLng}`"></p>
                </div>
                <div class="book-review-card">
                    <h3>Project</h3>
                    <p><strong x-text="projectName"></strong></p>
                    <p class="lede"><span x-text="typeLabel"></span>: <span x-text="projectType"></span></p>
                    <template x-for="field in extraFields" :key="`rev-${field.key}`">
                        <p class="lede" x-show="details[field.key]">
                            <span x-text="field.label"></span>: <span x-text="details[field.key]"></span>
                        </p>
                    </template>
                    <p class="lede">Package: <span x-text="packageLabel || packageType"></span> · <span x-text="money(sessionPrice)"></span></p>
                </div>
                <div class="book-review-card">
                    <h3>Brief</h3>
                    <p x-text="clientBrief"></p>
                    <p class="lede" x-show="notes" x-text="notes"></p>
                </div>
                <div class="book-review-card">
                    <h3>Estimate</h3>
                    <p>Session <strong x-text="money(sessionPrice)"></strong></p>
                    <p class="lede">Client platform fee ~10% is calculated at checkout. Funds stay in escrow until you approve delivery.</p>
                </div>

                <div class="cta-row">
                    <button class="ghost-btn" type="button" style="margin:0" @click="prev()">Back</button>
                    <button class="ai-btn" type="button" @click="next()">Continue to payment</button>
                </div>
            </div>

            {{-- Step 4: Payment (mobile BookCheckoutPage + BookPaymentPage) --}}
            <div class="site-shell-form" x-show="step === 4" x-cloak>
                <h2 style="margin-top:0">Secure checkout</h2>
                <p class="lede">Almost there — enter payment details, then confirm &amp; pay.</p>

                <div class="book-review-card">
                    <p>Session <strong x-text="money(sessionPrice)"></strong></p>
                    <p class="lede">Platform fee <span x-text="money(platformFee)"></span></p>
                    <p class="lede" x-show="travelFee > 0">Travel <span x-text="money(travelFee)"></span></p>
                    <p class="lede" x-show="discount > 0">Discount −<span x-text="money(discount)"></span></p>
                    <p style="margin-top:8px">Total held in escrow <strong style="color:var(--orange)" x-text="money(totalPaid)"></strong></p>
                </div>

                <p class="book-label">Payment method *</p>
                <div class="book-pay-methods">
                    <button type="button" class="package-option" :class="paymentMethod === 'card' && 'is-active'" @click="paymentMethod = 'card'">
                        <span><strong>Bank card</strong><br><small class="lede">Visa · Mastercard · Meeza</small></span>
                    </button>
                    <button type="button" class="package-option" :class="paymentMethod === 'wallet' && 'is-active'" @click="paymentMethod = 'wallet'">
                        <span><strong>Mobile wallet</strong><br><small class="lede">Vodafone · Orange · e&amp; · WE</small></span>
                    </button>
                    <button type="button" class="package-option" :class="paymentMethod === 'paypal' && 'is-active'" @click="paymentMethod = 'paypal'">
                        <span><strong>PayPal</strong><br><small class="lede">Pay with PayPal balance</small></span>
                    </button>
                </div>

                <div x-show="paymentMethod === 'card'" x-cloak class="book-pay-panel">
                    <label class="book-field"><span>Cardholder name *</span><input x-model="payment.card_holder" autocomplete="cc-name" placeholder="Name on card"></label>
                    <label class="book-field"><span>Card number *</span><input x-model="payment.card_number" inputmode="numeric" autocomplete="cc-number" placeholder="•••• •••• •••• ••••" maxlength="19"></label>
                    <div class="book-pay-row">
                        <label class="book-field"><span>Expiry *</span><input x-model="payment.card_expiry" placeholder="MM/YY" maxlength="5"></label>
                        <label class="book-field"><span>CVV *</span><input x-model="payment.card_cvv" inputmode="numeric" placeholder="CVV" maxlength="4"></label>
                    </div>
                    <p class="lede">Card number is not stored on Lens — only the last 4 digits are kept with the booking.</p>
                </div>

                <div x-show="paymentMethod === 'wallet'" x-cloak class="book-pay-panel">
                    <label class="book-field">
                        <span>Telecom *</span>
                        <select x-model="payment.wallet_telecom">
                            <option value="">Select wallet</option>
                            <option value="vodafone">Vodafone Cash</option>
                            <option value="orange">Orange Cash</option>
                            <option value="etisalat">e&amp; cash</option>
                            <option value="we">WE Pay</option>
                        </select>
                    </label>
                    <label class="book-field"><span>Wallet phone *</span><input x-model="payment.wallet_phone" inputmode="numeric" maxlength="11" placeholder="01xxxxxxxxx"></label>
                </div>

                <div x-show="paymentMethod === 'paypal'" x-cloak class="book-pay-panel">
                    <label class="book-field"><span>PayPal name *</span><input x-model="payment.paypal_name" placeholder="Account name"></label>
                    <label class="book-field"><span>PayPal email *</span><input type="email" x-model="payment.paypal_email" placeholder="name@email.com"></label>
                </div>

                <label class="book-field">
                    <span>Promo code</span>
                    <div class="book-promo-row">
                        <input name="promo_code" x-model="promoCode" placeholder="WELCOME200">
                        <button class="ghost-btn" type="button" style="margin:0" @click="applyPromo()">Apply</button>
                    </div>
                    <small class="lede" x-show="quoteError" x-text="quoteError" x-cloak style="color:#ff6b6b"></small>
                    <small class="lede" x-show="discount > 0" x-cloak style="color:#7dce9a">Saved <span x-text="money(discount)"></span></small>
                </label>

                <div class="cta-row">
                    <button class="ghost-btn" type="button" style="margin:0" @click="prev()">Back</button>
                    <button class="ai-btn" type="submit" :disabled="busy" x-text="busy ? 'Booking…' : 'Confirm & pay'"></button>
                </div>
                <p class="lede" style="margin-top:10px">Your payment is protected with bank-level security and held in escrow.</p>
            </div>
        </div>

        <aside class="site-shell-card book-aside">
            @if(filled($bookPhoto))
                <img src="{{ $bookPhoto }}" alt="" class="book-aside-photo">
            @endif
            <h3>{{ $vendor['display_name'] }}</h3>
            <p class="lede">{{ $vendor['city'] ?? '' }} · ★ {{ number_format((float) ($vendor['rating_avg'] ?? 0), 1) }}</p>
            <ul class="book-aside-list">
                <li><strong x-text="packageLabel || packageType || 'Package'"></strong></li>
                <li x-show="sessionPrice" x-text="money(sessionPrice)"></li>
                <li x-show="dateValue"><span x-text="dateValue"></span> · <span x-text="timeValue"></span></li>
                <li x-show="locationText" x-text="locationText"></li>
            </ul>
            <p class="lede">Funds stay in escrow until you approve delivery — same protection as mobile.</p>
        </aside>
    </form>

    <div class="book-map-modal" x-show="mapOpen" x-cloak @keydown.escape.window="closeMapPicker()">
        <div class="book-map-dialog" @click.stop>
            <header class="book-map-head">
                <button type="button" class="ghost-btn" style="margin:0" @click="closeMapPicker()">Back</button>
                <h3>Pin shoot location</h3>
                <button type="button" class="ghost-btn" style="margin:0" @click="locateOnMap(true)" :disabled="locating" x-text="locating ? 'Locating…' : 'GPS'"></button>
            </header>

            <div class="book-map-search">
                <input type="search" x-model="mapSearch" placeholder="Search Egypt areas…" @keydown.enter.prevent="searchMap()">
                <button type="button" class="ai-btn" @click="searchMap()" :disabled="mapSearching" x-text="mapSearching ? '…' : 'Search'"></button>
            </div>

            <div class="book-chip-row book-map-chips">
                <template x-for="place in (mapSuggestions.length ? mapSuggestions : mapPlaces)" :key="place.label">
                    <button type="button" class="book-chip" :class="mapDraft.label === place.label && 'is-active'" @click="setMapPlace(place)" x-text="place.label"></button>
                </template>
            </div>

            <div class="book-map-canvas" x-ref="bookMap"></div>

            <div class="book-map-foot">
                <div>
                    <strong x-text="mapDraft.label || 'Drop a pin'"></strong>
                    <small class="lede" x-show="mapGeocoding">Looking up address…</small>
                    <small class="lede" x-show="!mapGeocoding && mapDraft.lat" x-text="`${Number(mapDraft.lat).toFixed(5)}, ${Number(mapDraft.lng).toFixed(5)}`"></small>
                </div>
                <button type="button" class="ai-btn" @click="confirmMapLocation()">Use this location</button>
            </div>
        </div>
    </div>
</section>
@endsection
