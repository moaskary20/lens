(() => {
    const root = document.querySelector('[data-slider]');
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
})();

(() => {
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
    chips.forEach((chip) => chip.addEventListener('click', () => {
        chips.forEach((item) => item.classList.toggle('is-active', item === chip));
        const selected = chip.dataset.filter;
        shots.forEach((shot) => {
            shot.hidden = selected !== 'All' && shot.dataset.category !== selected;
        });
    }));
    root.querySelector('[data-showreel]')?.addEventListener('click', () => showTab('Portfolio'));
    root.querySelector('[data-share]')?.addEventListener('click', async () => {
        const url = window.location.href;
        try {
            await navigator.clipboard.writeText(url);
        } catch (error) {
            window.prompt('Copy profile link', url);
        }
    });
    showTab('Portfolio');
})();

(() => {
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
        const map = window.L.map(mapEl).setView([first.lat, first.lng], 11);
        window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap',
        }).addTo(map);
        vendors.forEach((vendor) => {
            window.L.marker([vendor.lat, vendor.lng])
                .addTo(map)
                .bindPopup(`<a href="${vendor.url}">${vendor.name}</a>`);
        });
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
})();

(() => {
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
})();
