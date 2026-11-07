@extends('site.layout', ['title' => 'Addresses', 'active' => 'account'])

@section('content')
<section class="acc-sub wrap" data-reveal>
    @include('site.account.partials.sub-hero', [
        'title' => 'Addresses',
        'lede' => 'Save shoot locations you use often for quicker booking.',
        'image' => asset('branding/account/addresses.svg'),
        'icon' => 'pin',
        'tips' => ['Home or studio labels', 'Used at checkout', 'Remove anytime'],
    ])

    <div class="acc-sub-body">
        <div class="acc-sub-stack">
            <div class="acc-sub-card">
                <header class="acc-sub-card-head">
                    <span class="acc-sub-card-ico" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 21s6-5.2 6-10a6 6 0 1 0-12 0c0 4.8 6 10 6 10z"/><circle cx="12" cy="11" r="2.2"/></svg>
                    </span>
                    <div>
                        <h2>Saved addresses</h2>
                        <p>Pick a location when you book a shoot.</p>
                    </div>
                </header>
                <div class="acc-sub-list">
                    @forelse((array) $addresses as $address)
                        <div class="acc-sub-row">
                            <span class="acc-sub-row-ico" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 21s6-5.2 6-10a6 6 0 1 0-12 0c0 4.8 6 10 6 10z"/><circle cx="12" cy="11" r="2.2"/></svg>
                            </span>
                            <div class="acc-sub-row-copy">
                                <strong>{{ $address['label'] ?? 'Address' }}</strong>
                                <small>{{ $address['line'] ?? $address['line1'] ?? '' }} {{ $address['city'] ?? '' }}</small>
                            </div>
                            <form method="post" action="{{ route('site.account.addresses.destroy', $address['id']) }}">
                                @csrf @method('DELETE')
                                <button class="ghost-btn acc-sub-remove" type="submit">Remove</button>
                            </form>
                        </div>
                    @empty
                        <div class="acc-sub-empty">
                            <strong>No addresses yet</strong>
                            <p>Save shoot locations for faster booking.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="acc-sub-card">
                <header class="acc-sub-card-head">
                    <span class="acc-sub-card-ico" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 5v14M5 12h14"/></svg>
                    </span>
                    <div>
                        <h2>Add address</h2>
                        <p>Label it Home, Studio, or a client location.</p>
                    </div>
                </header>
                <form method="post" action="{{ route('site.account.addresses.store') }}" class="acc-sub-form"
                      x-data="addressMapPicker({
                          mapsKey: @js(config('services.google.maps_key')),
                          searchUrl: @js(route('site.maps.search')),
                          reverseUrl: @js(route('site.maps.reverse')),
                          address: @js(old('line', '')),
                          lat: @js((float) old('latitude', 30.0444)),
                          lng: @js((float) old('longitude', 31.2357)),
                      })">
                    @csrf
                    <div class="acc-sub-grid">
                        <label><span class="acc-sub-label">Label</span><input name="label" value="{{ old('label') }}" placeholder="Home / Studio"></label>
                        <label><span class="acc-sub-label">City</span><input name="city" value="{{ old('city') }}" placeholder="Cairo"></label>
                        <div class="acc-sub-span-2 vj-address">
                            <input type="hidden" name="line" x-model="address" required>
                            <input type="hidden" name="latitude" x-model="lat">
                            <input type="hidden" name="longitude" x-model="lng">
                            <span class="acc-sub-label">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 21s6-5.2 6-10a6 6 0 1 0-12 0c0 4.8 6 10 6 10z"/><circle cx="12" cy="11" r="2.2"/></svg>
                                Address
                            </span>
                            <button type="button" class="book-map-trigger vj-address-trigger" @click="openPicker()">
                                <span class="book-map-trigger-ico" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 21s6-5.2 6-10a6 6 0 1 0-12 0c0 4.8 6 10 6 10z"/><circle cx="12" cy="11" r="2.2"/></svg>
                                </span>
                                <span class="book-map-trigger-copy">
                                    <strong x-text="preview"></strong>
                                    <small x-text="hasPin ? coordsLabel : 'Search or drop a pin on the map'"></small>
                                </span>
                                <span class="book-map-trigger-cta">Open map</span>
                            </button>
                            <div class="book-map-modal" x-show="open" x-cloak @keydown.escape.window="closePicker()">
                                <div class="book-map-dialog" @click.stop>
                                    <header class="book-map-head">
                                        <button type="button" class="ghost-btn" style="margin:0" @click="closePicker()">Back</button>
                                        <h3>Pick address</h3>
                                        <button type="button" class="ghost-btn" style="margin:0" @click="locate()" :disabled="locating" x-text="locating ? 'Locating…' : 'GPS'"></button>
                                    </header>
                                    <div class="book-map-search">
                                        <input type="search" x-ref="searchInput" x-model="search" placeholder="Search places in Egypt…" @keydown.enter.prevent="searchPlaces()">
                                        <button type="button" class="ai-btn" @click="searchPlaces()" :disabled="searching" x-text="searching ? '…' : 'Search'"></button>
                                    </div>
                                    <div class="book-chip-row book-map-chips">
                                        <template x-for="place in (suggestions.length ? suggestions : places)" :key="place.label">
                                            <button type="button" class="book-chip" :class="draft.label === place.label && 'is-active'" @click="setPlace(place)" x-text="place.label"></button>
                                        </template>
                                    </div>
                                    <div class="book-map-canvas vj-gmap-canvas" x-ref="mapCanvas"></div>
                                    <div class="book-map-foot">
                                        <div>
                                            <strong x-text="draft.label || 'Drop a pin'"></strong>
                                            <small class="lede" x-show="geocoding">Looking up address…</small>
                                            <small class="lede" x-show="!geocoding && draft.lat" x-text="`${Number(draft.lat).toFixed(5)}, ${Number(draft.lng).toFixed(5)}`"></small>
                                        </div>
                                        <button type="button" class="ai-btn" @click="confirm()">Use this address</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @foreach($errors->all() as $error)<em class="bookings-error">{{ $error }}</em>@endforeach
                    <button class="ai-btn" type="submit">Save address</button>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection
