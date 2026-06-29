/**
 * TNF Today — public site interactions (drawer, app mode, etc.)
 */

export function registerTnfSite(Alpine) {
    Alpine.data('tnfSite', () => ({
        isApp: false,

        init() {
            this.isApp = document.body.dataset.tnfApp === '1'
                || navigator.userAgent.includes('TNFTodayCapacitor');
        },
    }));
}

function isMobileNav() {
    return window.matchMedia('(max-width: 1023px)').matches;
}

export function openDrawer() {
    if (! isMobileNav()) {
        return;
    }

    document.body.classList.add('tnf-drawer-open');
    document.body.style.overflow = 'hidden';
    syncDrawerAria(true);
}

export function closeDrawer() {
    document.body.classList.remove('tnf-drawer-open');
    document.body.style.overflow = '';
    syncDrawerAria(false);
}

function syncDrawerAria(open) {
    document.querySelectorAll('[data-tnf-drawer-toggle]').forEach((toggle) => {
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
}

export function toggleDrawer() {
    if (document.body.classList.contains('tnf-drawer-open')) {
        closeDrawer();
    } else {
        openDrawer();
    }
}

let drawerListenersBound = false;
let drawerLastTouchAt = 0;

function handleDrawerClick(event) {
    // Mobile fires touchend then click — ignore the duplicate click.
    if (event.type === 'click' && Date.now() - drawerLastTouchAt < 600) {
        return;
    }

    const toggle = event.target.closest('[data-tnf-drawer-toggle]');

    if (toggle) {
        event.preventDefault();
        event.stopPropagation();
        toggleDrawer();

        return;
    }

    if (event.target.closest('[data-tnf-drawer-close]')) {
        closeDrawer();
    }
}

function handleDrawerTouchEnd(event) {
    drawerLastTouchAt = Date.now();

    const toggle = event.target.closest('[data-tnf-drawer-toggle]');

    if (toggle) {
        event.preventDefault();
        toggleDrawer();

        return;
    }

    if (event.target.closest('[data-tnf-drawer-close]')) {
        closeDrawer();
    }
}

function initDrawer() {
    if (drawerListenersBound) {
        return;
    }

    drawerListenersBound = true;

    document.addEventListener('click', handleDrawerClick, true);
    document.addEventListener('touchend', handleDrawerTouchEnd, { capture: true, passive: false });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeDrawer();
        }
    });

    window.tnfToggleDrawer = (event) => {
        event?.preventDefault?.();
        event?.stopPropagation?.();
        toggleDrawer();
    };

    window.tnfCloseDrawer = () => closeDrawer();
}

function initBackToTop() {
    const button = document.querySelector('.tnf-back-to-top');

    if (! button) {
        return;
    }

    const toggle = () => {
        button.classList.toggle('tnf-back-to-top--visible', window.scrollY > 320);
    };

    toggle();
    window.addEventListener('scroll', toggle, { passive: true });
}

function initShareButtons() {
    document.querySelectorAll('.tnf-share-copy').forEach((button) => {
        const copyIcon = button.querySelector('.tnf-share-copy-icon');
        const copiedIcon = button.querySelector('.tnf-share-copied-icon');

        button.addEventListener('click', async () => {
            const url = button.dataset.copyUrl;
            if (! url) {
                return;
            }

            try {
                await navigator.clipboard.writeText(url);
                copyIcon?.classList.add('hidden');
                copiedIcon?.classList.remove('hidden');
                button.classList.add('tnf-share-btn--copied');
                button.setAttribute('aria-label', 'Copied!');

                setTimeout(() => {
                    copyIcon?.classList.remove('hidden');
                    copiedIcon?.classList.add('hidden');
                    button.classList.remove('tnf-share-btn--copied');
                    button.setAttribute('aria-label', 'Copy link');
                }, 2000);
            } catch {
                button.setAttribute('aria-label', 'Copy failed');
            }
        });
    });

    if (navigator.share) {
        document.querySelectorAll('.tnf-share-native').forEach((button) => {
            button.classList.remove('hidden');
            button.addEventListener('click', async () => {
                try {
                    await navigator.share({
                        title: button.dataset.shareTitle || document.title,
                        url: button.dataset.shareUrl || window.location.href,
                    });
                } catch {
                    // User cancelled or share failed
                }
            });
        });
    }
}

function initMobileSafeArea() {
    if (! window.matchMedia('(max-width: 1023px)').matches) {
        return;
    }

    const root = document.documentElement;
    const headerChrome = 52; // 3.25rem

    const applyTopInset = (px) => {
        root.style.setProperty('--tnf-safe-top', `${px}px`);
        root.style.setProperty('--tnf-header-total', `${headerChrome + px}px`);
        document.body.classList.add('tnf-has-top-inset');
    };

    const probe = document.createElement('div');
    probe.style.cssText = 'position:fixed;visibility:hidden;padding-top:constant(safe-area-inset-top);padding-top:env(safe-area-inset-top);';
    document.body.appendChild(probe);
    const envTop = parseFloat(getComputedStyle(probe).paddingTop) || 0;
    probe.remove();

    if (envTop > 0) {
        applyTopInset(envTop);
        initHeaderMetrics();

        return;
    }

    const isStandalone = window.matchMedia('(display-mode: standalone)').matches
        || window.navigator.standalone === true;
    const isApp = document.body.dataset.tnfApp === '1';
    const isAndroid = /Android/i.test(navigator.userAgent);
    const immersiveViewport = window.innerHeight >= window.screen.height * 0.92;

    if (isStandalone || isApp || (isAndroid && immersiveViewport)) {
        applyTopInset(28);
    }

    initHeaderMetrics();
}

function initHeaderMetrics() {
    const header = document.querySelector('.tnf-header');

    if (! header || ! window.matchMedia('(max-width: 1023px)').matches) {
        return;
    }

    const apply = () => {
        const rect = header.getBoundingClientRect();
        const safeTop = parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--tnf-safe-top')) || 0;
        const chrome = Math.max(Math.round(rect.height - safeTop), 52);

        document.documentElement.style.setProperty('--tnf-header-chrome', `${chrome}px`);
        document.documentElement.style.setProperty('--tnf-header-total', `${Math.round(rect.height)}px`);
    };

    apply();

    if (typeof ResizeObserver !== 'undefined') {
        new ResizeObserver(apply).observe(header);
    }

    window.addEventListener('resize', apply, { passive: true });

    if (document.fonts?.ready) {
        document.fonts.ready.then(apply).catch(() => {});
    }
}

function initReadingProgress() {
    const bar = document.getElementById('tnf-reading-progress');
    const target = document.getElementById('tnf-reading-target');

    if (! bar || ! target) {
        return;
    }

    const update = () => {
        const rect = target.getBoundingClientRect();
        const total = target.offsetHeight - window.innerHeight;

        if (total <= 0) {
            bar.style.transform = 'scaleX(0)';
            return;
        }

        const scrolled = Math.min(Math.max(-rect.top, 0), total);
        bar.style.transform = `scaleX(${scrolled / total})`;
    };

    update();
    window.addEventListener('scroll', update, { passive: true });
    window.addEventListener('resize', update, { passive: true });
}

export function initSiteUi() {
    document.body.classList.remove('tnf-drawer-open');
    document.body.style.overflow = '';

    initDrawer();
    initBackToTop();
    initShareButtons();
    initMobileSafeArea();
    initReadingProgress();
}

document.addEventListener('alpine:init', () => {
    if (window.Alpine) {
        registerTnfSite(window.Alpine);
    }
});
