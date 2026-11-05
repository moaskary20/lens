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

document.addEventListener('alpine:init', () => {
    Alpine.data('bookFlow', (config = {}) => ({
        step: config.step || 1,
        vendorId: config.vendorId || null,
        packageType: config.packageType || '',
        sessionPrice: config.sessionPrice || '',
        scheduledAt: config.scheduledAt || '',
        durationHours: config.durationHours || 2,
        projectName: config.projectName || '',
        projectType: config.projectType || '',
        clientBrief: config.clientBrief || '',
        locationText: config.locationText || '',
        paymentMethod: config.paymentMethod || 'card',
        promoCode: config.promoCode || '',
        quote: config.quote || null,
        busy: false,
        error: '',
        next() {
            this.step = Math.min(5, this.step + 1);
        },
        prev() {
            this.step = Math.max(1, this.step - 1);
        },
        selectPackage(type, price) {
            this.packageType = type;
            this.sessionPrice = price;
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
    const swiper = new Swiper(root, {
        modules: [Navigation, Pagination, Autoplay, EffectFade],
        loop: true,
        effect: reduced ? 'slide' : 'fade',
        fadeEffect: { crossFade: true },
        speed: reduced ? 350 : 900,
        autoplay: reduced ? false : { delay: 5600, disableOnInteraction: false },
        pagination: { el: root.querySelector('.swiper-pagination'), clickable: true },
        navigation: {
            nextEl: root.querySelector('.swiper-button-next'),
            prevEl: root.querySelector('.swiper-button-prev'),
        },
        on: {
            init(instance) {
                animateHeroSlide(instance.slides[instance.activeIndex], { immediate: true });
            },
            slideChangeTransitionStart(instance) {
                animateHeroSlide(instance.slides[instance.activeIndex]);
            },
        },
    });
    window.__lensHeroSwiper = swiper;
}

function bootMagnetic(root = document) {
    if (prefersReducedMotion() || window.matchMedia('(pointer: coarse)').matches) {
        return;
    }
    root.querySelectorAll('[data-magnetic]').forEach((el) => {
        const child = el.querySelector('[data-magnetic-child]') || el;
        const strength = 18;
        el.addEventListener('pointermove', (event) => {
            const rect = el.getBoundingClientRect();
            const x = ((event.clientX - rect.left) / rect.width - 0.5) * strength;
            const y = ((event.clientY - rect.top) / rect.height - 0.5) * strength;
            gsap.to(child, { x, y, duration: 0.35, ease: 'power3.out' });
        });
        el.addEventListener('pointerleave', () => {
            gsap.to(child, { x: 0, y: 0, duration: 0.55, ease: 'power3.out' });
        });
    });
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

    const ai = home.querySelector('[data-home-ai]');
    if (ai) {
        if (reduced) {
            gsap.set(ai, { clearProps: 'all' });
        } else {
            gsap.fromTo(
                ai,
                { autoAlpha: 0, y: 40 },
                {
                    autoAlpha: 1,
                    y: 0,
                    duration: 0.85,
                    ease: 'power3.out',
                    delay: 0.25,
                },
            );
        }
        bootMagnetic(ai);
    }

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

function bootHomeBg3d() {
    const mounts = [...document.querySelectorAll('[data-home-bg3d]')];
    if (mounts.length === 0) {
        return;
    }
    if (prefersReducedMotion()) {
        mounts.forEach((mount) => mount.classList.add('is-fallback'));
        return;
    }
    import('./home-bg3d')
        .then(({ createHomeBg3d }) => {
            window.__lensHomeBg3d = mounts.map((mount) => {
                const scene = createHomeBg3d(mount, {
                    reducedMotion: false,
                    intensity: mount.dataset.homeBg3d === 'stage' ? 1.15 : 0.85,
                });
                if (!scene?.ok) {
                    mount.classList.add('is-fallback');
                }
                return scene;
            });
        })
        .catch(() => {
            mounts.forEach((mount) => mount.classList.add('is-fallback'));
        });
}

document.addEventListener('DOMContentLoaded', () => {
    bootLenis();
    bootHomeBg3d();
    bootHeroSwiper();
    bootHomeMotion();
    bootLegacySliderFallback();
    bootVendorLightbox();
    bootVendorProfile();
    bootCategorySheets();
    bootCardSliders();
    bootReveals();
});
