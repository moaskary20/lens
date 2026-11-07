import Alpine from 'alpinejs';
import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import Lenis from 'lenis';
import SplitType from 'split-type';
import Swiper from 'swiper';
import { Navigation, Pagination, Autoplay, EffectFade } from 'swiper/modules';

gsap.registerPlugin(ScrollTrigger);

const prefersReducedMotion = () =>
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;

window.Alpine = Alpine;

const BOOK_MAP_PLACES = [
    { label: 'Zamalek, Cairo', lat: 30.0626, lng: 31.2197 },
    { label: 'New Cairo, Cairo', lat: 30.03, lng: 31.47 },
    { label: 'Maadi, Cairo', lat: 29.9602, lng: 31.2569 },
    { label: 'Alexandria, Egypt', lat: 31.2001, lng: 29.9187 },
    { label: 'Giza, Egypt', lat: 30.0131, lng: 31.2089 },
];

function ensureLeaflet() {
    if (window.L) {
        return Promise.resolve(window.L);
    }
    if (window.__lensLeafletPromise) {
        return window.__lensLeafletPromise;
    }
    window.__lensLeafletPromise = new Promise((resolve, reject) => {
        const css = document.createElement('link');
        css.rel = 'stylesheet';
        css.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
        document.head.appendChild(css);
        const script = document.createElement('script');
        script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
        script.onload = () => resolve(window.L);
        script.onerror = () => reject(new Error('Could not load map'));
        document.body.appendChild(script);
    });
    return window.__lensLeafletPromise;
}

function ensureGoogleMaps(mapsKey) {
    if (window.__lensGoogleMapsFailed) {
        return Promise.reject(new Error('Google Maps unavailable'));
    }
    if (window.google?.maps?.Map && !window.__lensGoogleMapsFailed) {
        return Promise.resolve(window.google.maps);
    }
    if (!mapsKey) {
        return Promise.reject(new Error('Missing Google Maps key'));
    }
    if (window.__lensGoogleMapsPromise) {
        return window.__lensGoogleMapsPromise;
    }
    window.__lensGoogleMapsPromise = new Promise((resolve, reject) => {
        let settled = false;
        const fail = (reason) => {
            if (settled) {
                return;
            }
            settled = true;
            window.__lensGoogleMapsFailed = true;
            reject(reason instanceof Error ? reason : new Error(String(reason || 'Google Maps unavailable')));
        };
        const ok = (maps) => {
            if (settled) {
                return;
            }
            settled = true;
            resolve(maps);
        };
        const previousAuth = window.gm_authFailure;
        window.gm_authFailure = () => {
            try {
                previousAuth?.();
            } catch (_) {
                // ignore
            }
            fail(new Error('Google Maps auth failed'));
        };
        const previous = window.initLensGoogleMaps;
        window.initLensGoogleMaps = () => {
            window.initLensGoogleMaps = previous;
            // Auth failures often fire right after callback; wait briefly before accepting.
            window.setTimeout(() => {
                if (window.__lensGoogleMapsFailed) {
                    fail(new Error('Google Maps auth failed'));
                    return;
                }
                if (window.google?.maps?.Map) {
                    ok(window.google.maps);
                    return;
                }
                fail(new Error('Google Maps failed to load'));
            }, 120);
        };
        const script = document.createElement('script');
        script.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(mapsKey)}&libraries=places&v=weekly&callback=initLensGoogleMaps`;
        script.async = true;
        script.defer = true;
        script.onerror = () => fail(new Error('Could not load Google Maps'));
        document.head.appendChild(script);
        window.setTimeout(() => fail(new Error('Google Maps load timeout')), 2500);
    });
    return window.__lensGoogleMapsPromise;
}

document.addEventListener('alpine:init', () => {
    Alpine.data('bookFlow', (config = {}) => ({
        step: Number(config.step || 1),
        vendorId: config.vendorId || null,
        quoteUrl: config.quoteUrl || '',
        csrf: config.csrf || '',
        packages: Array.isArray(config.packages) ? config.packages : [],
        timeSlots: Array.isArray(config.timeSlots) ? config.timeSlots : ['10:00 AM', '12:00 PM', '2:00 PM', '4:00 PM', '6:00 PM'],
        typeLabel: config.typeLabel || 'Project type',
        extraFields: Array.isArray(config.extraFields) ? config.extraFields : [],
        packageType: config.packageType || '',
        packageLabel: config.packageLabel || '',
        sessionPrice: Number(config.sessionPrice || 0),
        durationHours: Number(config.durationHours || 0) || null,
        dateValue: config.dateValue || '',
        timeValue: config.timeValue || '10:00 AM',
        scheduledAt: config.scheduledAt || '',
        projectName: config.projectName || '',
        projectType: config.projectType || '',
        clientBrief: config.clientBrief || '',
        notes: config.notes || '',
        locationText: config.locationText || '',
        locationLat: config.locationLat || '',
        locationLng: config.locationLng || '',
        details: config.details || {},
        paymentMethod: config.paymentMethod || 'card',
        promoCode: config.promoCode || '',
        quote: config.quote || null,
        quoteError: '',
        payment: {
            card_holder: '',
            card_brand: '',
            card_last4: '',
            card_expiry: '',
            card_number: '',
            card_cvv: '',
            wallet_phone: '',
            wallet_telecom: '',
            paypal_email: '',
            paypal_name: '',
        },
        busy: false,
        locating: false,
        mapOpen: false,
        mapSearch: '',
        mapSearching: false,
        mapGeocoding: false,
        mapSuggestions: [],
        mapPlaces: BOOK_MAP_PLACES,
        mapDraft: {
            label: config.locationText || 'Cairo, Egypt',
            lat: Number(config.locationLat || 30.0444),
            lng: Number(config.locationLng || 31.2357),
        },
        _map: null,
        _marker: null,
        error: '',
        init() {
            if (!this.dateValue) {
                const today = new Date();
                this.dateValue = today.toISOString().slice(0, 10);
            }
            if (!this.packageType && this.packages.length) {
                this.selectPackage(this.packages[0]);
            }
            this.syncScheduledAt();
            this.extraFields.forEach((field) => {
                if (this.details[field.key] === undefined) {
                    this.details[field.key] = '';
                }
            });
        },
        next() {
            if (!this.validateStep(this.step)) {
                return;
            }
            this.error = '';
            this.step = Math.min(4, this.step + 1);
            window.scrollTo({ top: 0, behavior: 'smooth' });
            if (this.step === 4) {
                this.refreshQuote();
            }
        },
        prev() {
            this.error = '';
            this.step = Math.max(1, this.step - 1);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },
        selectPackage(pkg) {
            this.packageType = pkg.key || pkg.type || '';
            this.packageLabel = pkg.label || pkg.name || this.packageType;
            this.sessionPrice = Number(pkg.price || 0);
            this.durationHours = pkg.duration_hours != null ? Number(pkg.duration_hours) : this.durationFromKey(this.packageType);
        },
        durationFromKey(key) {
            const value = String(key || '');
            if (value.includes('full')) return 12;
            if (value.includes('hour')) return 1;
            if (value.includes('video')) return null;
            return 6;
        },
        syncScheduledAt() {
            if (!this.dateValue || !this.timeValue) {
                this.scheduledAt = '';
                return;
            }
            const match = String(this.timeValue).match(/^(\d{1,2}):(\d{2})\s*(AM|PM)$/i);
            if (!match) {
                this.scheduledAt = `${this.dateValue}T10:00`;
                return;
            }
            let hour = Number(match[1]);
            const minute = Number(match[2]);
            const meridiem = match[3].toUpperCase();
            if (meridiem === 'PM' && hour < 12) hour += 12;
            if (meridiem === 'AM' && hour === 12) hour = 0;
            this.scheduledAt = `${this.dateValue}T${String(hour).padStart(2, '0')}:${String(minute).padStart(2, '0')}`;
        },
        setDate(value) {
            this.dateValue = value;
            this.syncScheduledAt();
        },
        setTime(value) {
            this.timeValue = value;
            this.syncScheduledAt();
        },
        validateStep(step) {
            const missing = [];
            if (step === 1) {
                if (!this.dateValue) missing.push('date');
                if (!this.timeValue) missing.push('time');
            }
            if (step === 2) {
                if (!String(this.projectName || '').trim()) missing.push('project name');
                if (!String(this.projectType || '').trim()) missing.push(String(this.typeLabel || 'project type').toLowerCase());
                this.extraFields.forEach((field) => {
                    if (field.required && !String(this.details[field.key] || '').trim()) {
                        missing.push(String(field.label || field.key).toLowerCase());
                    }
                });
                if (!this.packageType || !(Number(this.sessionPrice) > 0)) missing.push('session price');
                if (!String(this.locationText || '').trim() || !(Number(this.locationLat) && Number(this.locationLng))) {
                    missing.push('map location');
                }
                if (!String(this.clientBrief || '').trim()) missing.push('short brief');
            }
            if (step === 4) {
                if (!this.paymentDetailsComplete()) {
                    this.error = `Open ${this.methodTitle(this.paymentMethod)} and enter the payment details first.`;
                    return false;
                }
            }
            if (missing.length) {
                this.error = `Please enter ${missing.join(', ')}.`;
                return false;
            }
            return true;
        },
        methodTitle(id) {
            return ({ wallet: 'Mobile wallet', paypal: 'PayPal', card: 'Bank card' })[id] || 'payment';
        },
        paymentDetailsComplete() {
            const p = this.payment;
            if (this.paymentMethod === 'card') {
                const last4 = String(p.card_last4 || p.card_number || '').replace(/\D+/g, '').slice(-4);
                return String(p.card_holder || '').trim() && last4.length === 4 && String(p.card_expiry || '').trim();
            }
            if (this.paymentMethod === 'wallet') {
                return String(p.wallet_phone || '').replace(/\D+/g, '').length === 11 && String(p.wallet_telecom || '').trim();
            }
            if (this.paymentMethod === 'paypal') {
                return String(p.paypal_email || '').includes('@') && String(p.paypal_name || '').trim();
            }
            return false;
        },
        paymentSummary() {
            const p = this.payment;
            if (this.paymentMethod === 'card') {
                const last4 = String(p.card_last4 || p.card_number || '').replace(/\D+/g, '').slice(-4);
                return [p.card_brand || 'Card', last4 ? `•••• ${last4}` : '', p.card_expiry].filter(Boolean).join('  ·  ');
            }
            if (this.paymentMethod === 'wallet') {
                return [p.wallet_telecom, p.wallet_phone].filter(Boolean).join('  ·  ');
            }
            return p.paypal_email || '';
        },
        async openMapPicker() {
            this.error = '';
            this.mapOpen = true;
            this.mapSearch = '';
            this.mapSuggestions = [];
            this.mapDraft = {
                label: this.locationText || 'Cairo, Egypt',
                lat: Number(this.locationLat || 30.0444),
                lng: Number(this.locationLng || 31.2357),
            };
            await this.$nextTick();
            try {
                await this.mountBookMap();
                if (!this.locationLat || !this.locationLng) {
                    this.locateOnMap(false);
                }
            } catch (_) {
                this.error = 'Could not load the map. Try again.';
                this.mapOpen = false;
            }
        },
        closeMapPicker() {
            this.mapOpen = false;
            if (this._map) {
                this._map.remove();
                this._map = null;
                this._marker = null;
            }
        },
        async mountBookMap() {
            const el = this.$refs.bookMap;
            if (!el) {
                return;
            }
            const L = await ensureLeaflet();
            if (this._map) {
                this._map.remove();
                this._map = null;
                this._marker = null;
            }
            this._map = L.map(el, { zoomControl: true }).setView([this.mapDraft.lat, this.mapDraft.lng], 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap',
                maxZoom: 19,
            }).addTo(this._map);
            this._marker = L.marker([this.mapDraft.lat, this.mapDraft.lng], { draggable: true }).addTo(this._map);
            this._marker.on('dragend', () => {
                const point = this._marker.getLatLng();
                this.dropMapPin(point.lat, point.lng, true);
            });
            this._map.on('click', (event) => {
                this.dropMapPin(event.latlng.lat, event.latlng.lng, true);
            });
            requestAnimationFrame(() => this._map?.invalidateSize());
            window.setTimeout(() => this._map?.invalidateSize(), 180);
        },
        setMapPlace(place) {
            this.mapDraft = {
                label: place.label,
                lat: Number(place.lat),
                lng: Number(place.lng),
            };
            if (this._map && this._marker) {
                const point = [this.mapDraft.lat, this.mapDraft.lng];
                this._marker.setLatLng(point);
                this._map.setView(point, Math.max(this._map.getZoom(), 13));
            }
        },
        async dropMapPin(lat, lng, reverse = false) {
            const nextLat = Number(Number(lat).toFixed(6));
            const nextLng = Number(Number(lng).toFixed(6));
            this.mapDraft = {
                label: reverse ? 'Moving pin…' : this.mapDraft.label,
                lat: nextLat,
                lng: nextLng,
            };
            if (this._marker) {
                this._marker.setLatLng([nextLat, nextLng]);
            }
            if (this._map) {
                this._map.panTo([nextLat, nextLng]);
            }
            if (!reverse) {
                return;
            }
            this.mapGeocoding = true;
            try {
                const label = await this.reverseGeocode(nextLat, nextLng);
                this.mapDraft = { label, lat: nextLat, lng: nextLng };
            } finally {
                this.mapGeocoding = false;
            }
        },
        async reverseGeocode(lat, lng) {
            try {
                const url = `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}&accept-language=en`;
                const res = await fetch(url, { headers: { Accept: 'application/json' } });
                if (!res.ok) {
                    throw new Error('geocode failed');
                }
                const data = await res.json();
                return data.display_name || `${lat.toFixed(4)}, ${lng.toFixed(4)}`;
            } catch (_) {
                return `${Number(lat).toFixed(4)}, ${Number(lng).toFixed(4)}`;
            }
        },
        async searchMap() {
            const query = String(this.mapSearch || '').trim();
            if (!query) {
                return;
            }
            this.mapSearching = true;
            try {
                const url = `https://nominatim.openstreetmap.org/search?format=jsonv2&limit=5&countrycodes=eg&q=${encodeURIComponent(query)}`;
                const res = await fetch(url, { headers: { Accept: 'application/json' } });
                const rows = res.ok ? await res.json() : [];
                const remote = (Array.isArray(rows) ? rows : []).map((row) => ({
                    label: row.display_name,
                    lat: Number(row.lat),
                    lng: Number(row.lon),
                })).filter((row) => row.label && row.lat && row.lng);
                const local = this.mapPlaces.filter((place) => place.label.toLowerCase().includes(query.toLowerCase()));
                this.mapSuggestions = remote.length ? remote : local;
                if (this.mapSuggestions.length) {
                    this.setMapPlace(this.mapSuggestions[0]);
                }
            } catch (_) {
                this.mapSuggestions = this.mapPlaces.filter((place) => place.label.toLowerCase().includes(query.toLowerCase()));
            } finally {
                this.mapSearching = false;
            }
        },
        async locateOnMap(moveDraft = true) {
            if (!navigator.geolocation) {
                this.error = 'Location is not supported in this browser.';
                return;
            }
            this.locating = true;
            this.error = '';
            try {
                const position = await new Promise((resolve, reject) => {
                    navigator.geolocation.getCurrentPosition(resolve, reject, { enableHighAccuracy: true, timeout: 12000 });
                });
                const lat = Number(position.coords.latitude.toFixed(6));
                const lng = Number(position.coords.longitude.toFixed(6));
                if (moveDraft || this.mapOpen) {
                    await this.dropMapPin(lat, lng, true);
                }
            } catch (_) {
                if (moveDraft) {
                    this.error = 'Could not read GPS. Search the map or pick a place.';
                }
            } finally {
                this.locating = false;
            }
        },
        confirmMapLocation() {
            if (!(this.mapDraft?.lat && this.mapDraft?.lng)) {
                this.error = 'Drop a pin on the map first.';
                return;
            }
            this.locationText = this.mapDraft.label || `${this.mapDraft.lat}, ${this.mapDraft.lng}`;
            this.locationLat = this.mapDraft.lat;
            this.locationLng = this.mapDraft.lng;
            this.error = '';
            this.closeMapPicker();
        },
        async pinLocation() {
            await this.openMapPicker();
        },
        async refreshQuote() {
            if (!this.quoteUrl || !this.vendorId) {
                return;
            }
            this.quoteError = '';
            try {
                const res = await fetch(this.quoteUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        vendor_id: this.vendorId,
                        package_type: this.packageType,
                        session_price: this.sessionPrice,
                        duration_hours: this.durationHours || this.durationFromKey(this.packageType),
                        promo_code: this.promoCode || null,
                        location_text: this.locationText,
                    }),
                });
                const data = await res.json();
                if (!res.ok) {
                    this.quote = null;
                    this.quoteError = data.message || data.errors?.promo_code?.[0] || 'Quote failed.';
                    return;
                }
                this.quote = data;
                if (data.session_price) {
                    this.sessionPrice = Number(data.session_price);
                }
            } catch (_) {
                this.quoteError = 'Could not refresh quote.';
            }
        },
        async applyPromo() {
            await this.refreshQuote();
        },
        beforeSubmit(event) {
            this.syncScheduledAt();
            if (!this.validateStep(4)) {
                event.preventDefault();
                return false;
            }
            const last4 = String(this.payment.card_last4 || this.payment.card_number || '').replace(/\D+/g, '').slice(-4);
            this.payment.card_last4 = last4;
            this.busy = true;
            return true;
        },
        money(value) {
            const amount = Math.round(Number(value || 0));
            return `EGP ${amount.toLocaleString('en-EG')}`;
        },
        get platformFee() {
            return Number(this.quote?.client_fee_amount ?? this.sessionPrice * 0.1);
        },
        get travelFee() {
            return Number(this.quote?.travel_fee ?? 0);
        },
        get discount() {
            return Number(this.quote?.discount_amount ?? 0);
        },
        get totalPaid() {
            if (this.quote?.total_paid != null) {
                return Number(this.quote.total_paid);
            }
            return Number(this.sessionPrice) + this.platformFee + this.travelFee - this.discount;
        },
    }));

    Alpine.data('vendorWizard', (config = {}) => ({
        step: config.step || 1,
        total: config.total || 6,
        editing: Boolean(config.editing),
        stepError: '',
        rootEl() {
            if (this.$el?.matches?.('section.vendor-join')) {
                return this.$el;
            }
            return this.$el?.closest?.('section.vendor-join') || this.$el;
        },
        joinForm() {
            return this.rootEl()?.querySelector('form.vendor-join-form, form.acc-sub-form') || null;
        },
        joinData() {
            const form = this.joinForm();
            return form ? window.Alpine.$data(form) : null;
        },
        fail(message) {
            this.stepError = message;
            window.scrollTo({ top: 0, behavior: 'smooth' });
            return false;
        },
        clearError() {
            this.stepError = '';
            return true;
        },
        validateBeforeLeave(fromStep) {
            const join = this.joinData();
            if (!join?.validateStep) {
                return this.fail('Complete the required fields before continuing.');
            }
            const error = join.validateStep(fromStep, this.editing);
            if (error) {
                return this.fail(error);
            }
            return this.clearError();
        },
        next() {
            if (!this.validateBeforeLeave(this.step)) {
                return false;
            }
            this.step = Math.min(this.total, this.step + 1);
            window.scrollTo({ top: 0, behavior: 'smooth' });
            return true;
        },
        prev() {
            this.clearError();
            this.step = Math.max(1, this.step - 1);
            window.scrollTo({ top: 0, behavior: 'smooth' });
            return true;
        },
        goTo(target) {
            const next = Math.min(this.total, Math.max(1, Number(target) || 1));
            if (next <= this.step) {
                this.clearError();
                this.step = next;
                window.scrollTo({ top: 0, behavior: 'smooth' });
                return true;
            }
            for (let current = this.step; current < next; current += 1) {
                if (!this.validateBeforeLeave(current)) {
                    this.step = current;
                    return false;
                }
                this.step = current + 1;
            }
            window.scrollTo({ top: 0, behavior: 'smooth' });
            return true;
        },
        beforeSubmit(event) {
            const join = this.joinData();
            if (!join?.validateStep) {
                event.preventDefault();
                return this.fail('Complete the required fields before submitting.');
            }
            for (let current = 1; current <= this.total; current += 1) {
                const error = join.validateStep(current, this.editing);
                if (error) {
                    event.preventDefault();
                    this.step = current;
                    this.fail(error);
                    return false;
                }
            }
            return this.clearError();
        },
    }));

    Alpine.data('vendorJoin', (config = {}) => ({
        catalog: Array.isArray(config.catalog) ? config.catalog : [],
        vendorType: config.vendorType || '',
        pricingModelId: config.pricingModelId ? String(config.pricingModelId) : '',
        texts: config.texts && typeof config.texts === 'object' ? { ...config.texts } : {},
        lists: config.lists && typeof config.lists === 'object' ? { ...config.lists } : {},
        filterTags: Array.isArray(config.filterTags) ? [...config.filterTags] : [],
        payoutMethod: config.payoutMethod || 'bank',
        walletNetwork: config.walletNetwork || 'telecom',
        optionalGear: [
            'extras.camera_type',
            'extras.lenses_available',
            'extras.lighting_gear',
            'extras.photo_extras',
            'extras.video_quality',
            'extras.video_gear',
            'extras.delivery_formats_notes',
            'extras.reels_setup',
            'extras.studio_features',
            'extras.studio_sets',
            'extras.studio_amenities',
            'extras.studio_equipment',
            'extras.model_sizes_notes',
            'extras.model_experience_notes',
            'extras.ugc_content_notes',
            'extras.ugc_gear_notes',
            'extras.food_expertise_notes',
            'extras.food_content_notes',
            'extras.addon_notes',
        ],
        init() {
            if (!this.vendorType && this.catalog.length) {
                this.vendorType = this.catalog[0].slug;
            }
            this.ensurePricingModel();
            this.typeFields.forEach((field) => {
                if (!String(field.key || '').startsWith('filter.')) {
                    return;
                }
                const options = (field.options || []).map((option) => option.id);
                this.lists[field.key] = this.filterTags.filter((slug) => options.includes(slug));
            });
        },
        wizard() {
            const root = this.$el?.closest?.('section.vendor-join');
            return root ? window.Alpine.$data(root) : null;
        },
        next() {
            return this.wizard()?.next() ?? false;
        },
        prev() {
            return this.wizard()?.prev() ?? false;
        },
        get spec() {
            return this.catalog.find((item) => item.slug === this.vendorType) || null;
        },
        get typeFields() {
            return Array.isArray(this.spec?.fields) ? this.spec.fields : [];
        },
        get pricingModels() {
            const models = Array.isArray(this.spec?.pricing_models) ? this.spec.pricing_models : [];
            if (models.length) {
                return models;
            }
            if (Array.isArray(this.spec?.pricing) && this.spec.pricing.length) {
                return [{
                    id: String(this.spec.pricing_model_id || 'default'),
                    name: this.spec.pricing_model || 'Pricing model',
                    fields: this.spec.pricing,
                }];
            }
            return [];
        },
        get selectedPricing() {
            return this.pricingModels.find((model) => String(model.id) === String(this.pricingModelId))
                || this.pricingModels[0]
                || null;
        },
        get pricingFields() {
            return Array.isArray(this.selectedPricing?.fields) ? this.selectedPricing.fields : [];
        },
        ensurePricingModel() {
            if (!this.pricingModels.length) {
                this.pricingModelId = '';
                return;
            }
            if (!this.pricingModels.some((model) => String(model.id) === String(this.pricingModelId))) {
                this.pricingModelId = String(this.pricingModels[0].id);
            }
        },
        selectType(slug) {
            this.vendorType = slug;
            this.pricingModelId = '';
            this.ensurePricingModel();
        },
        textValue(key) {
            return this.texts[key] ?? '';
        },
        setText(key, value) {
            this.texts[key] = value;
        },
        listValue(key) {
            return Array.isArray(this.lists[key]) ? this.lists[key] : [];
        },
        toggleList(key, id) {
            const current = this.listValue(key);
            const next = current.includes(id)
                ? current.filter((item) => item !== id)
                : [...current, id];
            this.lists[key] = next;
        },
        formName(key) {
            if (key === 'specialties' || key === 'delivery_formats') {
                return `${key}[]`;
            }
            if (String(key).startsWith('filter.')) {
                return 'filter_tags[]';
            }
            if (String(key).startsWith('extras.')) {
                return `extras${String(key).slice(7).split('.').map((part) => `[${part}]`).join('')}`;
            }
            return key;
        },
        collectedFilterTags() {
            const tags = [];
            this.typeFields.forEach((field) => {
                if (!String(field.key || '').startsWith('filter.')) {
                    return;
                }
                tags.push(...this.listValue(field.key));
            });
            return [...new Set(tags)];
        },
        fieldValue(name) {
            const el = this.$el.querySelector(`[name="${name}"]`);
            if (!el) {
                return '';
            }
            if (el.type === 'file') {
                return el.files?.length ? '1' : '';
            }
            return String(el.value || '').trim();
        },
        egyptPhone(value) {
            return /^01[0125][0-9]{8}$/.test(String(value || '').replace(/\D+/g, ''));
        },
        projectsData() {
            const el = this.$el.querySelector('[data-vj-projects]');
            return el ? window.Alpine.$data(el) : null;
        },
        validateStep(step, editing = false) {
            if (step === 1) {
                return this.validateIdentity(editing);
            }
            if (step === 2) {
                return this.validateTypeProfile();
            }
            if (step === 3) {
                return this.validateProjects();
            }
            if (step === 4) {
                return this.validatePricing();
            }
            if (step === 5) {
                return null;
            }
            if (step === 6) {
                return this.validatePayout();
            }
            return null;
        },
        validateIdentity(editing) {
            if (!editing) {
                if (!this.fieldValue('name')) {
                    return 'Enter your full name.';
                }
                if (!this.fieldValue('email')) {
                    return 'Enter your email.';
                }
                if (!this.fieldValue('password') || this.fieldValue('password').length < 8) {
                    return 'Enter a password with at least 8 characters.';
                }
            }
            if (!this.egyptPhone(this.fieldValue('phone'))) {
                return 'Enter a valid Egyptian phone (010 / 011 / 012 / 015).';
            }
            if (!this.vendorType) {
                return 'Choose a vendor type to load the matching Type profile.';
            }
            if (!this.fieldValue('display_name')) {
                return 'Enter your display name.';
            }
            if (!this.fieldValue('date_of_birth')) {
                return 'Enter your date of birth.';
            }
            if (!this.fieldValue('city_id')) {
                return 'Choose your home governorate.';
            }
            if (!this.fieldValue('profession')) {
                return 'Enter your profession / job title.';
            }
            const years = Number(this.fieldValue('years_experience'));
            if (Number.isNaN(years) || years < 0) {
                return 'Enter your years of experience.';
            }
            if (!this.fieldValue('bio')) {
                return 'Write a short About section.';
            }
            if (!this.fieldValue('contact_email')) {
                return 'Enter your contact email.';
            }
            if (!this.egyptPhone(this.fieldValue('whatsapp'))) {
                return 'Enter a valid WhatsApp number.';
            }
            if (!this.fieldValue('instagram') || this.fieldValue('instagram_followers') === '') {
                return 'Enter your Instagram link and followers count.';
            }
            if (!this.fieldValue('tiktok_url') || this.fieldValue('tiktok_followers') === '') {
                return 'Enter your TikTok link and followers count.';
            }
            if (!this.fieldValue('facebook_url') || this.fieldValue('facebook_followers') === '') {
                return 'Enter your Facebook link and followers count.';
            }
            if (!editing) {
                if (!this.fieldValue('profile_photo')) {
                    return 'Add your personal photo.';
                }
                if (!this.fieldValue('national_id_image')) {
                    return 'Upload your national ID card.';
                }
                if (!this.fieldValue('cover_image')) {
                    return 'Add a cover image.';
                }
            }
            if (!this.fieldValue('address') || !this.fieldValue('latitude') || !this.fieldValue('longitude')) {
                return 'Pin your map address.';
            }
            return null;
        },
        validateTypeProfile() {
            if (!this.vendorType) {
                return 'Choose a vendor type on Identity first.';
            }
            for (const field of this.typeFields) {
                if (this.optionalGear.includes(field.key)) {
                    continue;
                }
                if (field.type === 'multi' || field.type === 'tags') {
                    if (!this.listValue(field.key).length) {
                        return `Complete every Type profile field (${field.label}).`;
                    }
                } else if (!String(this.texts[field.key] || '').trim()) {
                    return `Complete every Type profile field (${field.label}).`;
                }
            }
            return null;
        },
        validateProjects() {
            const projects = this.projectsData()?.projects || [];
            const filled = projects.filter((project) => String(project.title || '').trim());
            if (!filled.length) {
                return 'Add at least one previous project.';
            }
            for (const project of filled) {
                const title = String(project.title || '').trim();
                if (project.type === 'link' && !String(project.external_url || '').trim()) {
                    return `Add a link for project “${title}”.`;
                }
                if (project.type !== 'link') {
                    const hasExisting = Array.isArray(project.existing) && project.existing.length > 0;
                    const hasIds = Array.isArray(project.ids) && project.ids.length > 0;
                    // New uploads are only in the file input; check matching card input.
                    const index = projects.indexOf(project);
                    const input = this.$el.querySelector(`[name="projects[${index}][files][]"]`);
                    const hasFiles = Boolean(input?.files?.length);
                    if (!hasExisting && !hasIds && !hasFiles) {
                        return `Upload media for project “${title}”.`;
                    }
                }
            }
            return null;
        },
        validatePricing() {
            if (!this.pricingModels.length) {
                return null;
            }
            if (!this.selectedPricing) {
                return 'Choose the pricing model assigned to this vendor type.';
            }
            for (const field of this.pricingFields) {
                if (!String(this.texts[field.key] || '').trim()) {
                    return `Fill every price field from ${this.selectedPricing.name}.`;
                }
            }
            return null;
        },
        validatePayout() {
            if (!this.payoutMethod) {
                return 'Choose a payout method.';
            }
            if (this.payoutMethod === 'bank') {
                if (!this.fieldValue('bank_name') || !this.fieldValue('bank_account_type')) {
                    return 'Choose bank and account type.';
                }
                for (const name of [
                    'bank_account_holder',
                    'bank_account_number',
                    'bank_iban',
                    'bank_swift',
                    'bank_branch',
                    'bank_branch_code',
                ]) {
                    if (!this.fieldValue(name)) {
                        return 'Fill every bank payout field.';
                    }
                }
            } else if (this.payoutMethod === 'wallet') {
                if (!this.egyptPhone(this.fieldValue('wallet_phone'))) {
                    return 'Enter a valid wallet phone number.';
                }
                if (!this.walletNetwork) {
                    return 'Choose whether the wallet is telecom or bank issued.';
                }
                if (this.walletNetwork === 'telecom' && !this.fieldValue('wallet_telecom')) {
                    return 'Choose the telecom company.';
                }
                if (this.walletNetwork === 'bank' && !this.fieldValue('wallet_bank_name')) {
                    return 'Choose the wallet bank.';
                }
            } else if (this.payoutMethod === 'paypal') {
                if (!this.fieldValue('paypal_email') || !this.fieldValue('paypal_name')) {
                    return 'Enter PayPal email and account name.';
                }
            }
            return null;
        },
    }));

    Alpine.data('vendorProjects', (config = {}) => ({
        projects: (() => {
            const incoming = Array.isArray(config.projects) ? config.projects : [];
            if (!incoming.length) {
                return [{
                    type: 'image',
                    title: '',
                    completed_on: '',
                    external_url: '',
                    description: '',
                    is_featured: false,
                    ids: [],
                    existing: [],
                }];
            }
            return incoming.map((project) => ({
                type: project.type || 'image',
                title: project.title || '',
                completed_on: project.completed_on || '',
                external_url: project.external_url || '',
                description: project.description || '',
                is_featured: Boolean(project.is_featured),
                ids: Array.isArray(project.ids) ? project.ids : (project.id ? [project.id] : []),
                existing: Array.isArray(project.files)
                    ? project.files.map((file) => ({
                        id: file.id,
                        name: file.name || 'Media',
                        url: file.url || null,
                        kind: file.kind || file.type || 'image',
                    }))
                    : [],
            }));
        })(),
        add() {
            this.projects.push({
                type: 'image',
                title: '',
                completed_on: '',
                external_url: '',
                description: '',
                is_featured: false,
                ids: [],
                existing: [],
            });
        },
        remove(index) {
            if (this.projects.length <= 1) {
                this.projects[0] = {
                    type: 'image',
                    title: '',
                    completed_on: '',
                    external_url: '',
                    description: '',
                    is_featured: false,
                    ids: [],
                    existing: [],
                };
                return;
            }
            this.projects.splice(index, 1);
        },
        removeExisting(projectIndex, fileIndex) {
            const project = this.projects[projectIndex];
            if (!project) {
                return;
            }
            const removed = project.existing.splice(fileIndex, 1)[0];
            if (removed?.id) {
                project.ids = project.ids.filter((id) => Number(id) !== Number(removed.id));
            }
        },
        acceptFor(type) {
            if (type === 'video') {
                return 'video/*';
            }
            if (type === 'link') {
                return '';
            }
            return 'image/*';
        },
    }));

    Alpine.data('vendorAvailability', () => ({
        defaultStart: '10:00',
        defaultEnd: '18:00',
        weeksAhead: 4,
        timeOptions: (() => {
            const out = [];
            for (let hour = 6; hour <= 23; hour += 1) {
                for (const minute of [0, 30]) {
                    if (hour === 23 && minute === 30) {
                        continue;
                    }
                    const value = `${String(hour).padStart(2, '0')}:${String(minute).padStart(2, '0')}`;
                    out.push(value);
                }
            }
            return out;
        })(),
        days: [
            { key: 'Sun', full: 'Sunday', on: true, start: '10:00', end: '18:00' },
            { key: 'Mon', full: 'Monday', on: true, start: '10:00', end: '18:00' },
            { key: 'Tue', full: 'Tuesday', on: true, start: '10:00', end: '18:00' },
            { key: 'Wed', full: 'Wednesday', on: true, start: '10:00', end: '18:00' },
            { key: 'Thu', full: 'Thursday', on: true, start: '10:00', end: '18:00' },
            { key: 'Fri', full: 'Friday', on: false, start: '10:00', end: '18:00' },
            { key: 'Sat', full: 'Saturday', on: true, start: '10:00', end: '18:00' },
        ],
        get activeCount() {
            return this.days.filter((day) => day.on).length;
        },
        get totalHours() {
            return this.days.reduce((sum, day) => sum + (day.on ? this.dayHours(day) : 0), 0);
        },
        minutes(value) {
            const [hh, mm] = String(value || '00:00').split(':').map(Number);
            return (hh * 60) + (mm || 0);
        },
        dayHours(day) {
            const diff = this.minutes(day.end) - this.minutes(day.start);
            return diff > 0 ? diff / 60 : 0;
        },
        dayHoursLabel(day) {
            const hours = this.dayHours(day);
            if (!day.on) {
                return 'Closed';
            }
            if (hours <= 0) {
                return 'Invalid';
            }
            const whole = Number.isInteger(hours) ? hours : hours.toFixed(1);
            return `${whole}h`;
        },
        barStyle(day) {
            const startMin = 6 * 60;
            const endMin = 23 * 60;
            const span = endMin - startMin;
            const from = Math.min(Math.max(this.minutes(day.start), startMin), endMin);
            const to = Math.min(Math.max(this.minutes(day.end), startMin), endMin);
            if (!day.on || to <= from) {
                return 'display:none';
            }
            const left = ((from - startMin) / span) * 100;
            const width = ((to - from) / span) * 100;
            return `left:${left}%;width:${width}%`;
        },
        setWeekdays() {
            this.days.forEach((day, index) => {
                day.on = index >= 1 && index <= 5;
                if (day.on) {
                    day.start = this.defaultStart;
                    day.end = this.defaultEnd;
                }
            });
        },
        setAll(on) {
            this.days.forEach((day) => {
                day.on = on;
                if (on) {
                    day.start = this.defaultStart;
                    day.end = this.defaultEnd;
                }
            });
        },
        applyDefault() {
            this.days.forEach((day) => {
                if (!day.on) {
                    return;
                }
                day.start = this.defaultStart;
                day.end = this.defaultEnd;
            });
        },
        applyPreset(preset) {
            if (preset === 'standard') {
                this.defaultStart = '10:00';
                this.defaultEnd = '18:00';
                this.setWeekdays();
                return;
            }
            if (preset === 'early') {
                this.defaultStart = '09:00';
                this.defaultEnd = '17:00';
                this.setWeekdays();
                return;
            }
            if (preset === 'late') {
                this.defaultStart = '12:00';
                this.defaultEnd = '20:00';
                this.setWeekdays();
                return;
            }
            if (preset === 'weekend') {
                this.days.forEach((day, index) => {
                    day.on = index === 0 || index === 6;
                    day.start = '11:00';
                    day.end = '19:00';
                });
                this.defaultStart = '11:00';
                this.defaultEnd = '19:00';
            }
        },
        copyToOpen(sourceIndex) {
            const source = this.days[sourceIndex];
            if (!source) {
                return;
            }
            this.days.forEach((day, index) => {
                if (index === sourceIndex || !day.on) {
                    return;
                }
                day.start = source.start;
                day.end = source.end;
            });
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
            for (let week = 0; week < this.weeksAhead; week += 1) {
                this.days.forEach((day, weekday) => {
                    if (!day.on || this.dayHours(day) <= 0) {
                        return;
                    }
                    out.push({
                        starts_at: this.slotIso(weekday, day.start, week),
                        ends_at: this.slotIso(weekday, day.end, week),
                        status: 'open',
                    });
                });
            }
            return out;
        },
    }));

    Alpine.data('addressMapPicker', (config = {}) => ({
        mapsKey: config.mapsKey || '',
        searchUrl: config.searchUrl || '/maps/search',
        reverseUrl: config.reverseUrl || '/maps/reverse',
        open: false,
        searching: false,
        locating: false,
        geocoding: false,
        engine: 'auto',
        search: '',
        suggestions: [],
        places: Array.isArray(config.places) ? config.places : BOOK_MAP_PLACES,
        address: config.address || '',
        lat: config.lat != null ? Number(config.lat) : 30.0444,
        lng: config.lng != null ? Number(config.lng) : 31.2357,
        draft: {
            label: config.address || 'Cairo, Egypt',
            lat: config.lat != null ? Number(config.lat) : 30.0444,
            lng: config.lng != null ? Number(config.lng) : 31.2357,
        },
        _map: null,
        _marker: null,
        _autocomplete: null,
        _engine: null,
        get hasPin() {
            return Boolean(String(this.address || '').trim() && this.lat && this.lng);
        },
        get preview() {
            return String(this.address || '').trim() || 'Pick your address on the map';
        },
        get coordsLabel() {
            if (!(this.lat && this.lng)) {
                return 'No pin yet';
            }
            return `${Number(this.lat).toFixed(5)}, ${Number(this.lng).toFixed(5)}`;
        },
        async openPicker() {
            this.open = true;
            this.draft = {
                label: this.address || this.draft.label || 'Cairo, Egypt',
                lat: Number(this.lat || this.draft.lat || 30.0444),
                lng: Number(this.lng || this.draft.lng || 31.2357),
            };
            this.search = '';
            this.suggestions = [];
            await this.$nextTick();
            await this.mountMap();
        },
        closePicker() {
            this.open = false;
        },
        confirm() {
            if (!(this.draft?.lat && this.draft?.lng)) {
                return;
            }
            this.address = this.draft.label || `${this.draft.lat}, ${this.draft.lng}`;
            this.lat = Number(Number(this.draft.lat).toFixed(6));
            this.lng = Number(Number(this.draft.lng).toFixed(6));
            this.closePicker();
        },
        resetMapCanvas(el) {
            if (this._map?.remove && this._engine === 'leaflet') {
                try {
                    this._map.remove();
                } catch (_) {
                    // ignore
                }
            }
            this._map = null;
            this._marker = null;
            this._autocomplete = null;
            this._engine = null;
            if (el) {
                el.innerHTML = '';
            }
        },
        async mountMap() {
            const el = this.$refs.mapCanvas;
            if (!el) {
                return;
            }
            // Do not mount Google Maps JS here: the mobile key loads but then paints
            // "Sorry! Something went wrong" when Maps JavaScript API / referrers are not set for web.
            // Leaflet + server /maps/* (Google key then Nominatim) is the reliable web path.
            if (this._map && this._engine === 'leaflet') {
                this._map.setView([this.draft.lat, this.draft.lng], Math.max(this._map.getZoom?.() || 13, 13));
                this._marker?.setLatLng?.([this.draft.lat, this.draft.lng]);
                window.setTimeout(() => this._map?.invalidateSize?.(), 80);
                window.setTimeout(() => this._map?.invalidateSize?.(), 280);
                return;
            }
            this.resetMapCanvas(el);
            await this.mountLeaflet(el);
        },
        async mountLeaflet(el) {
            const L = await ensureLeaflet();
            this._engine = 'leaflet';
            this.engine = 'leaflet';
            this._map = L.map(el, { zoomControl: true, scrollWheelZoom: true }).setView(
                [this.draft.lat, this.draft.lng],
                13,
            );
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap',
            }).addTo(this._map);
            this._marker = L.marker([this.draft.lat, this.draft.lng], { draggable: true }).addTo(this._map);
            this._map.on('click', (event) => this.dropPin(event.latlng.lat, event.latlng.lng, true));
            this._marker.on('dragend', () => {
                const point = this._marker.getLatLng();
                this.dropPin(point.lat, point.lng, true);
            });
            window.setTimeout(() => this._map?.invalidateSize?.(), 80);
            window.setTimeout(() => this._map?.invalidateSize?.(), 280);
        },
        setPlace(place) {
            this.draft = {
                label: place.label,
                lat: Number(place.lat),
                lng: Number(place.lng),
            };
            this.dropPin(place.lat, place.lng, false);
            if (this._map?.setView) {
                this._map.setView([this.draft.lat, this.draft.lng], Math.max(this._map.getZoom?.() || 13, 14));
                this._marker?.setLatLng([this.draft.lat, this.draft.lng]);
            }
        },
        async dropPin(lat, lng, reverse = false) {
            const nextLat = Number(Number(lat).toFixed(6));
            const nextLng = Number(Number(lng).toFixed(6));
            this.draft = {
                label: reverse ? 'Looking up address…' : this.draft.label,
                lat: nextLat,
                lng: nextLng,
            };
            if (this._marker?.setLatLng) {
                this._marker.setLatLng([nextLat, nextLng]);
                this._map?.panTo?.([nextLat, nextLng]);
            }
            if (!reverse) {
                return;
            }
            this.geocoding = true;
            try {
                this.draft = {
                    label: await this.reverseGeocode(nextLat, nextLng),
                    lat: nextLat,
                    lng: nextLng,
                };
            } finally {
                this.geocoding = false;
            }
        },
        async reverseGeocode(lat, lng) {
            try {
                const url = `${this.reverseUrl}?lat=${encodeURIComponent(lat)}&lng=${encodeURIComponent(lng)}`;
                const res = await fetch(url, { headers: { Accept: 'application/json' } });
                if (res.ok) {
                    const data = await res.json();
                    if (data?.label) {
                        return data.label;
                    }
                }
            } catch (_) {
                // fall through
            }
            return `${Number(lat).toFixed(4)}, ${Number(lng).toFixed(4)}`;
        },
        async searchPlaces() {
            const query = String(this.search || '').trim();
            if (!query) {
                return;
            }
            this.searching = true;
            try {
                const url = `${this.searchUrl}?q=${encodeURIComponent(query)}`;
                const res = await fetch(url, { headers: { Accept: 'application/json' } });
                const data = res.ok ? await res.json() : { results: [] };
                const remote = (Array.isArray(data?.results) ? data.results : [])
                    .map((row) => ({
                        label: row.label,
                        lat: Number(row.lat),
                        lng: Number(row.lng),
                    }))
                    .filter((row) => row.label && row.lat && row.lng);
                const local = this.places.filter((place) => place.label.toLowerCase().includes(query.toLowerCase()));
                this.suggestions = remote.length ? remote : local;
                if (this.suggestions.length) {
                    this.setPlace(this.suggestions[0]);
                }
            } catch (_) {
                this.suggestions = this.places.filter((place) => place.label.toLowerCase().includes(query.toLowerCase()));
            } finally {
                this.searching = false;
            }
        },
        async locate() {
            if (!navigator.geolocation) {
                return;
            }
            this.locating = true;
            try {
                const position = await new Promise((resolve, reject) => {
                    navigator.geolocation.getCurrentPosition(resolve, reject, { enableHighAccuracy: true, timeout: 12000 });
                });
                await this.dropPin(position.coords.latitude, position.coords.longitude, true);
            } finally {
                this.locating = false;
            }
        },
    }));

    Alpine.data('aiSearch', (config = {}) => ({
        chatUrl: config.chatUrl || '',
        csrf: config.csrf || '',
        enabled: Boolean(config.enabled),
        prompt: config.prompt || 'What will you create today?',
        helper: config.helper || 'Describe your idea and let AI find the right creatives for you.',
        voiceInput: Boolean(config.voiceInput),
        moodboardOn: Boolean(config.moodboardOn),
        moodboardLocked: Boolean(config.moodboardLocked),
        cities: Array.isArray(config.cities) ? config.cities : [],
        vendorUrl: config.vendorUrl || '/creatives',
        steps: ['location', 'date', 'budget'],
        lines: [{
            role: 'assistant',
            body: 'I ask location, then date, then budget — the same Lens assistant from the app. Then I compare real creators and help you pick.',
        }],
        draft: '',
        conversationId: null,
        ask: null,
        status: 'gathering',
        provider: 'lexicon',
        slots: {},
        vendors: [],
        comparison: [],
        pick: null,
        addOns: [],
        busy: false,
        init() {
            const seed = String(config.seed || '').trim();
            if (seed && this.enabled) {
                this.$nextTick(() => this.send(seed));
            }
        },
        get quickReplies() {
            if (this.ask === 'location') {
                return this.cities.slice(0, 6);
            }
            if (this.ask === 'date') {
                return ['This weekend', 'Next week', 'Next month'];
            }
            if (this.ask === 'budget') {
                return ['Low', 'Mid', 'High'];
            }
            return [];
        },
        slotFilled(step) {
            const slots = this.slots || {};
            if (step === 'location') {
                return Boolean(slots.governorate || slots.city_id);
            }
            if (step === 'date') {
                return Boolean(slots.available_on || slots.relative_date);
            }
            return Boolean(slots.budget_band || slots.min_price || slots.max_price);
        },
        initials(name) {
            return String(name || 'L')
                .split(/\s+/)
                .filter(Boolean)
                .slice(0, 2)
                .map((part) => part[0]?.toUpperCase() || '')
                .join('') || 'L';
        },
        reset() {
            this.conversationId = null;
            this.ask = null;
            this.status = 'gathering';
            this.slots = {};
            this.vendors = [];
            this.comparison = [];
            this.pick = null;
            this.addOns = [];
            this.busy = false;
            this.draft = '';
            this.lines = [{
                role: 'assistant',
                body: 'New brief. Tell me what you want to create.',
            }];
        },
        jump() {
            this.$nextTick(() => {
                const el = this.$refs.thread;
                if (el) {
                    el.scrollTop = el.scrollHeight + 80;
                }
            });
        },
        async send(preset) {
            const text = String(preset ?? this.draft ?? '').trim();
            if (!text || this.busy || !this.enabled) {
                return;
            }
            this.draft = '';
            this.lines.push({ role: 'user', body: text });
            this.busy = true;
            this.jump();
            try {
                const res = await fetch(this.chatUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        message: text,
                        conversation_id: this.conversationId,
                    }),
                });
                const data = await res.json();
                if (!res.ok) {
                    throw new Error(data.message || 'Assistant unavailable.');
                }
                this.conversationId = data.conversation_id || this.conversationId;
                this.ask = data.ask || null;
                this.status = data.status || 'gathering';
                this.provider = data.provider || this.provider;
                this.slots = data.slots || {};
                this.vendors = Array.isArray(data.vendors) ? data.vendors : [];
                this.comparison = Array.isArray(data.comparison) ? data.comparison : [];
                this.pick = data.pick || null;
                this.addOns = Array.isArray(data.add_ons) ? data.add_ons : (Array.isArray(data.addOns) ? data.addOns : []);
                this.lines.push({ role: 'assistant', body: data.reply || 'Got it.' });
            } catch (error) {
                this.lines.push({
                    role: 'assistant',
                    body: error?.message || 'Something went wrong. Try again.',
                });
            } finally {
                this.busy = false;
                this.jump();
            }
        },
    }));

    Alpine.data('chatThread', (config = {}) => ({
        messages: config.messages || [],
        body: '',
        busy: false,
        pollUrl: config.pollUrl || null,
        sendUrl: config.sendUrl || null,
        csrf: config.csrf || '',
        timer: null,
        init() {
            if (this.pollUrl) {
                this.timer = window.setInterval(() => this.poll(), 4000);
            }
        },
        destroy() {
            if (this.timer) {
                window.clearInterval(this.timer);
            }
        },
        async poll() {
            try {
                const res = await fetch(this.pollUrl, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!res.ok) {
                    return;
                }
                const data = await res.json();
                if (Array.isArray(data.messages)) {
                    this.messages = data.messages;
                    this.$nextTick(() => this.scrollBottom());
                }
            } catch (_) {
                // ignore transient poll errors
            }
        },
        async send() {
            const text = this.body.trim();
            if (!text || this.busy || !this.sendUrl) {
                return;
            }
            this.busy = true;
            try {
                const res = await fetch(this.sendUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ body: text }),
                });
                const data = await res.json();
                if (res.ok && Array.isArray(data.messages)) {
                    this.messages = data.messages;
                    this.body = '';
                    this.$nextTick(() => this.scrollBottom());
                }
            } finally {
                this.busy = false;
            }
        },
        scrollBottom() {
            const el = this.$refs.scroller;
            if (el) {
                el.scrollTop = el.scrollHeight;
            }
        },
    }));

    Alpine.data('favoriteToggle', (config = {}) => ({
        on: !!config.on,
        busy: false,
        async toggle() {
            if (this.busy || !config.url) {
                return;
            }
            this.busy = true;
            const method = this.on ? 'DELETE' : 'POST';
            try {
                const res = await fetch(config.url, {
                    method,
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': config.csrf || '',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                if (res.ok) {
                    this.on = !this.on;
                } else if (res.status === 401 || res.status === 302) {
                    window.location.href = config.loginUrl || '/login';
                }
            } finally {
                this.busy = false;
            }
        },
    }));
});

Alpine.start();

function bootLenis() {
    const lenis = new Lenis({
        duration: 1.05,
        smoothWheel: true,
    });
    lenis.on('scroll', ScrollTrigger.update);
    gsap.ticker.add((time) => {
        lenis.raf(time * 1000);
    });
    gsap.ticker.lagSmoothing(0);
    window.__lensLenis = lenis;
}

function bootReveals() {
    const reduced = prefersReducedMotion();
    document.querySelectorAll('[data-reveal]').forEach((el) => {
        if (el.closest('[data-home-motion]')) {
            return;
        }
        if (reduced) {
            el.classList.add('is-in');
            gsap.set(el, { clearProps: 'all' });
            return;
        }
        gsap.fromTo(
            el,
            { autoAlpha: 0, y: 28 },
            {
                autoAlpha: 1,
                y: 0,
                duration: 0.7,
                ease: 'power2.out',
                scrollTrigger: {
                    trigger: el,
                    start: 'top 88%',
                    once: true,
                },
                onStart: () => el.classList.add('is-in'),
            },
        );
    });
}

function animateHeroSlide(slide, { immediate = false } = {}) {
    if (!slide) {
        return;
    }
    const media = slide.querySelector('.hero-media');
    const lines = [...slide.querySelectorAll('[data-hero-line]')];
    const reduced = prefersReducedMotion();

    gsap.killTweensOf([media, ...lines, ...slide.querySelectorAll('.line, .word, .char')]);

    if (media) {
        if (reduced || immediate) {
            gsap.set(media, { scale: 1, xPercent: 0, yPercent: 0 });
        } else {
            gsap.fromTo(
                media,
                { scale: 1.12, xPercent: -1.5, yPercent: 0 },
                { scale: 1.02, xPercent: 1.2, yPercent: -0.6, duration: 7.5, ease: 'none' },
            );
        }
    }

    lines.forEach((line) => {
        if (line.dataset.split === '1' && line._split) {
            try {
                line._split.revert();
            } catch {
                // ignore broken split state
            }
            delete line._split;
            delete line.dataset.split;
        }
        gsap.set(line, { clearProps: 'opacity,visibility,transform' });
    });

    if (reduced) {
        gsap.set(lines, { autoAlpha: 1, y: 0 });
        return;
    }

    let words = [];
    try {
        const splits = lines.map((line) => {
            const split = new SplitType(line, { types: 'lines,words', lineClass: 'hero-line' });
            line.dataset.split = '1';
            line._split = split;
            return split;
        });
        words = splits.flatMap((split) => split.words || []);
    } catch {
        gsap.fromTo(
            lines,
            { autoAlpha: 0, y: 18 },
            {
                autoAlpha: 1,
                y: 0,
                duration: immediate ? 0.4 : 0.65,
                stagger: 0.06,
                ease: 'power3.out',
            },
        );
        return;
    }

    if (words.length === 0) {
        gsap.set(lines, { autoAlpha: 1, y: 0 });
        return;
    }

    gsap.set(words, { autoAlpha: 0, yPercent: 120 });
    gsap.to(words, {
        autoAlpha: 1,
        yPercent: 0,
        duration: immediate ? 0.45 : 0.8,
        stagger: 0.035,
        ease: 'power3.out',
        delay: immediate ? 0 : 0.12,
    });
}

function bootHeroSwiper() {
    const root = document.querySelector('[data-hero-swiper]');
    if (!root) {
        return;
    }
    if (root.dataset.swiperReady === '1') {
        return;
    }
    root.dataset.swiperReady = '1';

    const reduced = prefersReducedMotion();
    const delay = 5600;
    const progress = root.querySelector('[data-hero-progress]');
    const prevBtn = root.querySelector('.swiper-button-prev');
    const nextBtn = root.querySelector('.swiper-button-next');
    const pager = root.querySelector('.swiper-pagination');
    const slideCount = root.querySelectorAll('.swiper-wrapper > .swiper-slide').length;

    const restartProgress = () => {
        if (!progress || reduced) {
            return;
        }
        progress.style.transition = 'none';
        progress.style.width = '0%';
        void progress.offsetWidth;
        progress.style.transition = `width ${delay}ms linear`;
        progress.style.width = '100%';
    };

    const pauseProgress = () => {
        if (!progress || reduced) {
            return;
        }
        const computed = getComputedStyle(progress).width;
        progress.style.transition = 'none';
        progress.style.width = computed;
    };

    const activeSlide = (instance) =>
        instance?.slides?.[instance.activeIndex]
        || root.querySelector('.swiper-slide-active')
        || null;

    const swiper = new Swiper(root, {
        modules: [Navigation, Pagination, Autoplay],
        loop: false,
        rewind: slideCount > 1,
        slidesPerView: 1,
        spaceBetween: 0,
        speed: reduced ? 320 : 650,
        grabCursor: true,
        allowTouchMove: true,
        autoplay: reduced
            ? false
            : {
                delay,
                disableOnInteraction: false,
                pauseOnMouseEnter: true,
            },
        pagination: pager
            ? {
                el: pager,
                clickable: true,
                dynamicBullets: false,
            }
            : undefined,
        navigation: {
            nextEl: nextBtn,
            prevEl: prevBtn,
        },
        on: {
            init(instance) {
                try {
                    animateHeroSlide(activeSlide(instance), { immediate: true });
                } catch (error) {
                    console.warn('hero slide animation failed', error);
                }
                restartProgress();
            },
            slideChange(instance) {
                try {
                    animateHeroSlide(activeSlide(instance));
                } catch (error) {
                    console.warn('hero slide animation failed', error);
                }
                restartProgress();
            },
            autoplayStop() {
                pauseProgress();
            },
            autoplayStart() {
                restartProgress();
            },
        },
    });

    window.__lensHeroSwiper = swiper;
}

function bootCategoryParallax(root) {
    if (prefersReducedMotion() || window.matchMedia('(pointer: coarse)').matches) {
        return;
    }
    root.querySelectorAll('[data-cat-card]').forEach((card) => {
        const media = card.querySelector('.cat-media');
        if (!media) {
            return;
        }
        card.addEventListener('pointermove', (event) => {
            const rect = card.getBoundingClientRect();
            const x = ((event.clientX - rect.left) / rect.width - 0.5) * 10;
            const y = ((event.clientY - rect.top) / rect.height - 0.5) * 8;
            gsap.to(media, { x, y, scale: 1.06, duration: 0.4, ease: 'power2.out' });
            gsap.to(card, { y: -4, duration: 0.35, ease: 'power2.out' });
        });
        card.addEventListener('pointerleave', () => {
            gsap.to(media, { x: 0, y: 0, scale: 1, duration: 0.5, ease: 'power2.out' });
            gsap.to(card, { y: 0, duration: 0.45, ease: 'power2.out' });
        });
    });
}

function bootSearchAiLink() {
    const link = document.querySelector('[data-srch-ai]');
    const input = document.querySelector('.srch-bar input[name="q"]');
    if (!link) {
        return;
    }
    link.addEventListener('click', (event) => {
        const q = (input?.value || '').trim();
        try {
            const url = new URL(link.getAttribute('href') || '/ai', window.location.origin);
            if (q !== '') {
                url.searchParams.set('q', q);
            } else {
                url.searchParams.delete('q');
            }
            link.href = `${url.pathname}${url.search}`;
        } catch {
            event.preventDefault();
            window.location.href = q !== '' ? `/ai?q=${encodeURIComponent(q)}` : '/ai';
        }
    });
}

function bootSearchMotion() {
    const hero = document.querySelector('[data-search-motion]');
    if (!hero) {
        return;
    }
    const reduced = prefersReducedMotion();
    const media = hero.querySelector('[data-srch-media]');
    const copy = hero.querySelector('[data-srch-copy]');
    const types = document.querySelector('[data-srch-types]');
    const cards = [...document.querySelectorAll('[data-srch-grid] .vendor-card')];

    if (reduced) {
        gsap.set([media, copy, types, ...cards].filter(Boolean), { clearProps: 'all' });
        return;
    }

    if (media) {
        gsap.fromTo(
            media,
            { scale: 1.12, yPercent: 3 },
            { scale: 1.04, yPercent: 0, duration: 1.2, ease: 'power2.out' },
        );
    }
    if (copy) {
        gsap.fromTo(
            copy,
            { autoAlpha: 0, y: 24 },
            { autoAlpha: 1, y: 0, duration: 0.7, delay: 0.1, ease: 'power3.out' },
        );
    }
    if (types) {
        gsap.fromTo(
            types.querySelectorAll('.srch-type'),
            { autoAlpha: 0, y: 18 },
            {
                autoAlpha: 1,
                y: 0,
                duration: 0.5,
                stagger: 0.04,
                ease: 'power2.out',
                scrollTrigger: { trigger: types, start: 'top 92%', once: true },
            },
        );
    }
    if (cards.length) {
        gsap.set(cards, { autoAlpha: 0, y: 28 });
        ScrollTrigger.batch(cards, {
            start: 'top 93%',
            once: true,
            onEnter: (batch) => {
                gsap.to(batch, {
                    autoAlpha: 1,
                    y: 0,
                    duration: 0.6,
                    stagger: 0.06,
                    ease: 'power3.out',
                });
            },
        });
    }
}

function bootAccountMotion() {
    const root = document.querySelector('[data-account-motion]');
    if (!root) {
        return;
    }
    const reduced = prefersReducedMotion();
    const media = root.querySelector('[data-acc-media]');
    const copy = root.querySelector('[data-acc-copy]');
    const head = document.querySelector('[data-acc-head]');
    const tiles = [...document.querySelectorAll('[data-acc-tile]')];
    const secondary = document.querySelector('[data-acc-secondary]');

    if (reduced) {
        gsap.set([media, copy, head, ...tiles, secondary].filter(Boolean), { clearProps: 'all' });
        return;
    }

    if (media) {
        gsap.fromTo(
            media,
            { scale: 1.12, yPercent: 4 },
            { scale: 1.05, yPercent: 0, duration: 1.35, ease: 'power2.out' },
        );
        gsap.to(media, {
            yPercent: -4,
            ease: 'none',
            scrollTrigger: {
                trigger: root,
                start: 'top top',
                end: 'bottom top',
                scrub: true,
            },
        });
    }

    if (copy) {
        gsap.fromTo(
            copy,
            { autoAlpha: 0, y: 28 },
            { autoAlpha: 1, y: 0, duration: 0.75, delay: 0.12, ease: 'power3.out' },
        );
    }

    if (head) {
        gsap.fromTo(
            head,
            { autoAlpha: 0, y: 16 },
            {
                autoAlpha: 1,
                y: 0,
                duration: 0.55,
                ease: 'power2.out',
                scrollTrigger: { trigger: head, start: 'top 90%', once: true },
            },
        );
    }

    if (tiles.length) {
        gsap.set(tiles, { autoAlpha: 0, y: 32, scale: 0.97 });
        ScrollTrigger.batch(tiles, {
            start: 'top 92%',
            once: true,
            onEnter: (batch) => {
                gsap.to(batch, {
                    autoAlpha: 1,
                    y: 0,
                    scale: 1,
                    duration: 0.65,
                    stagger: 0.07,
                    ease: 'power3.out',
                });
            },
        });
    }

    if (secondary) {
        gsap.fromTo(
            secondary,
            { autoAlpha: 0, y: 20 },
            {
                autoAlpha: 1,
                y: 0,
                duration: 0.55,
                ease: 'power2.out',
                scrollTrigger: { trigger: secondary, start: 'top 92%', once: true },
            },
        );
    }
}

function bootHomeMotion() {
    const home = document.querySelector('[data-home-motion]');
    if (!home) {
        return;
    }
    const reduced = prefersReducedMotion();

    const cats = home.querySelector('[data-home-cats]');
    if (cats) {
        const head = cats.querySelector('.block-head');
        const cards = [...cats.querySelectorAll('[data-cat-card]')];
        const coarse = window.matchMedia('(pointer: coarse)').matches;
        if (reduced || coarse) {
            gsap.set([head, ...cards].filter(Boolean), { clearProps: 'all' });
        } else {
            gsap.set(cards, { autoAlpha: 0, y: 28 });
            ScrollTrigger.batch(cards, {
                start: 'top 92%',
                once: true,
                onEnter: (batch) => {
                    gsap.to(batch, {
                        autoAlpha: 1,
                        y: 0,
                        duration: 0.65,
                        stagger: 0.05,
                        ease: 'power3.out',
                        overwrite: 'auto',
                    });
                },
            });
            if (head) {
                gsap.fromTo(
                    head,
                    { autoAlpha: 0, y: 18 },
                    {
                        autoAlpha: 1,
                        y: 0,
                        duration: 0.6,
                        ease: 'power2.out',
                        scrollTrigger: { trigger: cats, start: 'top 90%', once: true },
                    },
                );
            }
        }
        bootCategoryParallax(cats);
    }

    home.querySelectorAll('[data-home-rail]').forEach((rail) => {
        const head = rail.querySelector('[data-rail-head]');
        const cards = [...rail.querySelectorAll('.vendor-card')];
        if (reduced) {
            gsap.set([head, ...cards], { clearProps: 'all' });
            return;
        }
        gsap.set(cards, { autoAlpha: 0, y: 28 });
        if (head) {
            gsap.fromTo(
                head,
                { autoAlpha: 0, y: 16 },
                {
                    autoAlpha: 1,
                    y: 0,
                    duration: 0.55,
                    ease: 'power2.out',
                    scrollTrigger: { trigger: rail, start: 'top 88%', once: true },
                },
            );
        }
        ScrollTrigger.batch(cards, {
            start: 'top 93%',
            once: true,
            onEnter: (batch) => {
                gsap.to(batch, {
                    autoAlpha: 1,
                    y: 0,
                    duration: 0.65,
                    stagger: 0.08,
                    ease: 'power3.out',
                });
            },
        });
    });
}

function bootLegacySliderFallback() {
    const root = document.querySelector('[data-slider]:not([data-hero-swiper])');
    if (!root) {
        return;
    }
    const slides = [...root.querySelectorAll('.hero-slide')];
    const dots = [...root.querySelectorAll('[data-dot]')];
    let index = 0;
    let timer;
    const show = (next) => {
        index = (next + slides.length) % slides.length;
        slides.forEach((slide, i) => slide.classList.toggle('is-active', i === index));
        dots.forEach((dot, i) => dot.classList.toggle('is-active', i === index));
    };
    const play = () => {
        window.clearInterval(timer);
        timer = window.setInterval(() => show(index + 1), 5600);
    };
    root.querySelector('[data-prev]')?.addEventListener('click', () => {
        show(index - 1);
        play();
    });
    root.querySelector('[data-next]')?.addEventListener('click', () => {
        show(index + 1);
        play();
    });
    dots.forEach((dot) => {
        dot.addEventListener('click', () => {
            show(Number(dot.dataset.dot));
            play();
        });
    });
    play();
}

function bootVendorProfile() {
    const root = document.querySelector('[data-vendor-profile]');
    if (!root) {
        return;
    }
    const tabs = [...root.querySelectorAll('[data-tab]')];
    const panels = [...root.querySelectorAll('[data-panel]')];
    const chips = [...root.querySelectorAll('[data-filter]')];
    const shots = [...root.querySelectorAll('[data-shot]')];
    const filters = root.querySelector('[data-filters]');
    const showTab = (name) => {
        tabs.forEach((tab) => tab.classList.toggle('is-active', tab.dataset.tab === name));
        panels.forEach((panel) => {
            panel.hidden = panel.dataset.panel !== name;
        });
        if (filters) {
            filters.hidden = name !== 'Portfolio';
        }
    };
    tabs.forEach((tab) => tab.addEventListener('click', () => showTab(tab.dataset.tab)));
    chips.forEach((chip) =>
        chip.addEventListener('click', () => {
            chips.forEach((item) => item.classList.toggle('is-active', item === chip));
            const selected = chip.dataset.filter;
            shots.forEach((shot) => {
                shot.hidden = selected !== 'All' && shot.dataset.category !== selected;
            });
        }),
    );
    const galleryUrls = () =>
        shots
            .filter((shot) => !shot.hidden)
            .map((shot) => shot.dataset.url)
            .filter(Boolean);
    shots.forEach((shot) => {
        shot.addEventListener('click', () => {
            const urls = galleryUrls();
            const url = shot.dataset.url;
            const start = Math.max(0, urls.indexOf(url));
            if (urls.length > 0 && window.__lensOpenGallery) {
                window.__lensOpenGallery(urls, start);
            }
        });
    });
    const showreel = root.querySelector('[data-showreel]');
    showreel?.addEventListener('click', () => {
        let gallery = [];
        try {
            gallery = JSON.parse(showreel.dataset.gallery || '[]');
        } catch (_) {
            gallery = [];
        }
        if (gallery.length === 0) {
            gallery = galleryUrls();
        }
        if (gallery.length > 0 && window.__lensOpenGallery) {
            window.__lensOpenGallery(gallery, 0);
            return;
        }
        showTab('Portfolio');
    });
    root.querySelector('[data-share]')?.addEventListener('click', async () => {
        const url = window.location.href;
        try {
            if (navigator.share) {
                await navigator.share({ title: document.title, url });
                return;
            }
            await navigator.clipboard.writeText(url);
        } catch (_) {
            window.prompt('Copy profile link', url);
        }
    });
    showTab('Portfolio');
}

function bootVendorLightbox() {
    const box = document.getElementById('vp-lightbox');
    if (!box) {
        return;
    }
    const image = box.querySelector('[data-lightbox-image]');
    let gallery = [];
    let index = 0;
    const render = () => {
        if (!image || !gallery[index]) {
            return;
        }
        image.src = gallery[index];
    };
    const open = (urls, start = 0) => {
        gallery = urls.filter(Boolean);
        if (gallery.length === 0) {
            return;
        }
        index = start % gallery.length;
        box.hidden = false;
        document.body.style.overflow = 'hidden';
        render();
    };
    const close = () => {
        box.hidden = true;
        document.body.style.overflow = '';
    };
    window.__lensOpenGallery = open;
    box.querySelector('[data-lightbox-close]')?.addEventListener('click', close);
    box.querySelector('[data-lightbox-prev]')?.addEventListener('click', () => {
        index = (index - 1 + gallery.length) % gallery.length;
        render();
    });
    box.querySelector('[data-lightbox-next]')?.addEventListener('click', () => {
        index = (index + 1) % gallery.length;
        render();
    });
    box.addEventListener('click', (event) => {
        if (event.target === box) {
            close();
        }
    });
    document.addEventListener('keydown', (event) => {
        if (box.hidden) {
            return;
        }
        if (event.key === 'Escape') {
            close();
        }
        if (event.key === 'ArrowLeft') {
            index = (index - 1 + gallery.length) % gallery.length;
            render();
        }
        if (event.key === 'ArrowRight') {
            index = (index + 1) % gallery.length;
            render();
        }
    });
}

function bootCategorySheets() {
    const page = document.querySelector('[data-category-page]');
    if (!page) {
        return;
    }
    const sheets = {
        sort: document.getElementById('sort-sheet'),
        filter: document.getElementById('filter-sheet'),
    };
    const close = () => {
        Object.values(sheets).forEach((sheet) => {
            if (sheet) {
                sheet.hidden = true;
            }
        });
    };
    document.querySelectorAll('[data-open]').forEach((button) => {
        button.addEventListener('click', () => {
            close();
            const sheet = sheets[button.dataset.open];
            if (sheet) {
                sheet.hidden = false;
            }
        });
    });
    document.querySelectorAll('[data-close]').forEach((button) => {
        button.addEventListener('click', close);
    });
    Object.values(sheets).forEach((sheet) => {
        sheet?.addEventListener('click', (event) => {
            if (event.target === sheet) {
                close();
            }
        });
    });

    const mapEl = document.getElementById('cat-map');
    if (!mapEl) {
        return;
    }
    const vendors = JSON.parse(mapEl.dataset.vendors || '[]');
    const boot = () => {
        if (!window.L) {
            return;
        }
        const first = vendors[0] || { lat: 30.0444, lng: 31.2357 };
        const map = window.L.map(mapEl, {
            zoomControl: true,
        }).setView([first.lat, first.lng], 11);
        window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap',
        }).addTo(map);
        vendors.forEach((vendor) => {
            window.L.marker([vendor.lat, vendor.lng])
                .addTo(map)
                .bindPopup(`<a href="${vendor.url}">${vendor.name}</a>`);
        });
        // Keep tiles inside the map box so Sort / Recommended stay above the map.
        requestAnimationFrame(() => map.invalidateSize());
        window.setTimeout(() => map.invalidateSize(), 180);
    };
    if (window.L) {
        boot();
        return;
    }
    const css = document.createElement('link');
    css.rel = 'stylesheet';
    css.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
    document.head.appendChild(css);
    const script = document.createElement('script');
    script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
    script.onload = boot;
    document.body.appendChild(script);
}

function bootCardSliders() {
    document.querySelectorAll('[data-card-slider]').forEach((root) => {
        const rail = root.querySelector('[data-card-rail]');
        const prev = root.querySelector('[data-card-prev]');
        const next = root.querySelector('[data-card-next]');
        if (!rail || !prev || !next) {
            return;
        }
        const step = () => {
            const card = rail.querySelector('.vendor-card, .cat');
            if (!card) {
                return rail.clientWidth * 0.8;
            }
            const styles = window.getComputedStyle(rail);
            const gap = Number.parseFloat(styles.columnGap || styles.gap || '16') || 16;
            return card.getBoundingClientRect().width + gap;
        };
        const sync = () => {
            const max = Math.max(0, rail.scrollWidth - rail.clientWidth - 2);
            prev.disabled = rail.scrollLeft <= 2;
            next.disabled = rail.scrollLeft >= max;
        };
        prev.addEventListener('click', () => {
            rail.scrollBy({ left: -step(), behavior: 'smooth' });
        });
        next.addEventListener('click', () => {
            rail.scrollBy({ left: step(), behavior: 'smooth' });
        });
        rail.addEventListener('scroll', sync, { passive: true });
        window.addEventListener('resize', sync);
        sync();
    });
}


function bootCmsAccordion() {
    document.querySelectorAll('[data-cms-acc]').forEach((root) => {
        root.addEventListener('toggle', (event) => {
            const target = event.target;
            if (!(target instanceof HTMLDetailsElement) || !target.open) {
                return;
            }
            root.querySelectorAll('details.cms-acc-item').forEach((item) => {
                if (item !== target) {
                    item.open = false;
                }
            });
        }, true);
    });
}

function bootOpenInApp() {
    const ua = navigator.userAgent || '';
    const isAndroid = /Android/i.test(ua);
    const isIOS = /iPhone|iPad|iPod/i.test(ua);
    if (!isAndroid && !isIOS) {
        return;
    }

    const packageName = 'app.lens.lens';
    const playStore = `https://play.google.com/store/apps/details?id=${packageName}`;
    const dismissedKey = 'lens_app_banner_dismissed';
    const triedKey = 'lens_app_open_tried';

    const pathToDeepLink = () => {
        const path = window.location.pathname.replace(/\/+$/, '') || '/';
        const creatives = path.match(/^\/creatives\/(\d+)/);
        if (creatives) {
            return `vendor/${creatives[1]}`;
        }
        const book = path.match(/^\/book\/(\d+)/);
        if (book) {
            return `book/${book[1]}`;
        }
        if (path === '/ai') {
            return 'ai';
        }
        if (path === '/search' || path === '/explore') {
            return 'search';
        }
        if (path === '/bookings') {
            return 'bookings';
        }
        return 'home';
    };

    const openApp = ({ withStoreFallback = false } = {}) => {
        const deep = pathToDeepLink();
        const fallback = withStoreFallback ? playStore : window.location.href;
        if (isAndroid) {
            const intent = `intent://${deep}#Intent;scheme=lens;package=${packageName};S.browser_fallback_url=${encodeURIComponent(fallback)};end`;
            window.location.href = intent;
            return;
        }
        // iOS custom scheme — if the app is missing the page simply stays put.
        const started = Date.now();
        window.location.href = `lens://${deep}`;
        window.setTimeout(() => {
            if (document.hidden || Date.now() - started > 1800) {
                return;
            }
            if (withStoreFallback) {
                window.location.href = playStore;
            }
        }, 1200);
    };

    const banner = document.querySelector('[data-app-open-banner]');
    const openBtn = document.querySelector('[data-app-open-btn]');
    const dismissBtn = document.querySelector('[data-app-open-dismiss]');

    const showBanner = () => {
        if (!banner || localStorage.getItem(dismissedKey) === '1') {
            return;
        }
        banner.hidden = false;
        document.body.classList.add('has-app-banner');
    };

    openBtn?.addEventListener('click', () => openApp({ withStoreFallback: true }));
    dismissBtn?.addEventListener('click', () => {
        localStorage.setItem(dismissedKey, '1');
        if (banner) {
            banner.hidden = true;
        }
        document.body.classList.remove('has-app-banner');
    });

    showBanner();

    // One automatic open attempt per browser session when the app may be installed.
    if (sessionStorage.getItem(triedKey) === '1' || localStorage.getItem(dismissedKey) === '1') {
        return;
    }
    sessionStorage.setItem(triedKey, '1');
    // Delay slightly so the first paint is not interrupted.
    window.setTimeout(() => openApp({ withStoreFallback: false }), 400);
}

document.addEventListener('DOMContentLoaded', () => {
    bootLenis();
    bootHeroSwiper();
    bootHomeMotion();
    bootAccountMotion();
    bootSearchMotion();
    bootSearchAiLink();
    bootLegacySliderFallback();
    bootVendorLightbox();
    bootVendorProfile();
    bootCategorySheets();
    bootCardSliders();
    bootReveals();
    bootCmsAccordion();
    bootOpenInApp();
});
