(() => {
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let sidebarPlayed = false;

    const mark = (selector, animation, step = 70) => {
        document.querySelectorAll(selector).forEach((el, index) => {
            if (el.dataset.lensMotion) {
                return;
            }

            el.dataset.lensMotion = '1';
            el.setAttribute('data-aos', animation);
            el.setAttribute('data-aos-delay', String(Math.min(index * step, 420)));
            el.setAttribute('data-aos-duration', '650');
        });
    };

    const decorate = () => {
        mark('.fi-wi-stats-overview-stat', 'fade-up', 90);
        mark('.fi-wi-widget, .fi-section, .fi-ta-ctn, .fi-fo-component-ctn', 'fade-up', 60);
        mark('.fi-ta-row', 'fade-up', 28);
        mark('.fi-simple-card', 'zoom-in', 0);
        document.querySelectorAll('.fi-btn-primary, .fi-ac-btn-action').forEach((el) => {
            el.classList.add('lens-pulse-btn');
        });
    };

    const initAos = () => {
        if (!window.AOS || reduced) {
            return;
        }

        if (!document.body.dataset.aosReady) {
            window.AOS.init({
                duration: 650,
                easing: 'ease-out-cubic',
                once: true,
                offset: 28,
                disable: reduced,
            });
            document.body.dataset.aosReady = '1';
        } else {
            window.AOS.refreshHard();
        }
    };

    const playSidebar = () => {
        if (reduced || sidebarPlayed || !window.anime) {
            return;
        }

        const items = document.querySelectorAll('.fi-sidebar-item, .fi-sidebar-group-btn');
        if (!items.length) {
            return;
        }

        sidebarPlayed = true;
        window.anime({
            targets: items,
            opacity: [0, 1],
            delay: window.anime.stagger(18, { start: 40 }),
            duration: 360,
            easing: 'easeOutCubic',
        });
    };

    const playLogin = () => {
        if (reduced || !window.anime) {
            return;
        }

        const stage = document.querySelector('.lens-login');
        const card = document.querySelector('.lens-login .fi-simple-page-content, .fi-simple-card');
        if (!card || card.dataset.lensLogin) {
            return;
        }

        card.dataset.lensLogin = '1';
        window.anime({
            targets: card,
            translateY: [28, 0],
            opacity: [0, 1],
            duration: 780,
            easing: 'easeOutExpo',
        });

        const fields = card.querySelectorAll('.fi-fo-field-wrp, .fi-ac-btn-action, .fi-btn');
        if (fields.length) {
            window.anime({
                targets: fields,
                translateY: [14, 0],
                opacity: [0, 1],
                delay: window.anime.stagger(70, { start: 180 }),
                duration: 520,
                easing: 'easeOutCubic',
            });
        }

        if (stage) {
            window.anime({
                targets: '.lens-login-copy > *',
                translateY: [18, 0],
                opacity: [0, 1],
                delay: window.anime.stagger(90),
                duration: 700,
                easing: 'easeOutExpo',
            });
        }

        window.anime({
            targets: '.lens-orb',
            scale: [0.85, 1.08],
            opacity: [0.35, 0.7],
            direction: 'alternate',
            loop: true,
            delay: window.anime.stagger(400),
            duration: 2800,
            easing: 'easeInOutSine',
        });
    };

    const boot = () => {
        decorate();
        initAos();
        playSidebar();
        playLogin();
    };

    document.addEventListener('DOMContentLoaded', boot);
    document.addEventListener('livewire:navigated', () => {
        sidebarPlayed = false;
        boot();
    });
    document.addEventListener('livewire:init', () => {
        if (!window.Livewire) {
            return;
        }

        window.Livewire.hook('morphed', () => {
            decorate();
            initAos();
        });
    });
})();
