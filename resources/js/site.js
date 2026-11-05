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
        next() {
            this.step = Math.min(this.total, this.step + 1);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },
        prev() {
            this.step = Math.max(1, this.step - 1);
            window.scrollTo({ top: 0, behavior: 'smooth' });
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
            line._split.revert();
            delete line._split;
            delete line.dataset.split;
        }
    });

    if (reduced) {
        gsap.set(lines, { autoAlpha: 1, y: 0 });
        return;
    }

    const splits = lines.map((line) => {
        const split = new SplitType(line, { types: 'lines,words', lineClass: 'hero-line' });
        line.dataset.split = '1';
        line._split = split;
        return split;
    });

    const words = splits.flatMap((split) => split.words || []);
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
    const reduced = prefersReducedMotion();
    const delay = 5600;
    const progress = root.querySelector('[data-hero-progress]');

    const restartProgress = () => {
        if (!progress || reduced) {
            return;
        }
        progress.style.transition = 'none';
        progress.style.width = '0%';
        // Force reflow so the width reset applies before the fill animation.
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

    const swiper = new Swiper(root, {
        modules: [Navigation, Pagination, Autoplay, EffectFade],
        loop: true,
        effect: reduced ? 'slide' : 'fade',
        fadeEffect: { crossFade: true },
        speed: reduced ? 350 : 900,
        autoplay: reduced ? false : { delay, disableOnInteraction: false },
        pagination: { el: root.querySelector('.swiper-pagination'), clickable: true },
        navigation: {
            nextEl: root.querySelector('.swiper-button-next'),
            prevEl: root.querySelector('.swiper-button-prev'),
        },
        on: {
            init(instance) {
                animateHeroSlide(instance.slides[instance.activeIndex], { immediate: true });
                restartProgress();
            },
            slideChangeTransitionStart(instance) {
                animateHeroSlide(instance.slides[instance.activeIndex]);
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
        if (reduced) {
            gsap.set([head, ...cards], { clearProps: 'all' });
        } else {
            gsap.set(cards, { autoAlpha: 0, y: 36, scale: 0.96 });
            ScrollTrigger.batch(cards, {
                start: 'top 92%',
                once: true,
                onEnter: (batch) => {
                    gsap.to(batch, {
                        autoAlpha: 1,
                        y: 0,
                        scale: 1,
                        duration: 0.7,
                        stagger: 0.06,
                        ease: 'power3.out',
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

document.addEventListener('DOMContentLoaded', () => {
    bootLenis();
    bootHeroSwiper();
    bootHomeMotion();
    bootLegacySliderFallback();
    bootVendorLightbox();
    bootVendorProfile();
    bootCategorySheets();
    bootCardSliders();
    bootReveals();
    bootCmsAccordion();
});
