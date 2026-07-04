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

function setDrawerInert(open) {
    const main = document.getElementById('tnf-main');
    const bottomNav = document.querySelector('.tnf-bottom-nav');
    const backToTop = document.querySelector('.tnf-back-to-top');
    const pwaBanner = document.getElementById('tnf-pwa-install-banner');

    [main, bottomNav, backToTop, pwaBanner].forEach((element) => {
        if (! element) {
            return;
        }

        if (open) {
            element.setAttribute('inert', '');
        } else {
            element.removeAttribute('inert');
        }
    });
}

export function openDrawer() {
    if (! isMobileNav()) {
        return;
    }

    document.body.classList.add('tnf-drawer-open');
    document.body.style.overflow = 'hidden';
    setDrawerInert(true);
    syncDrawerAria(true);
}

export function closeDrawer({ suppressGhostClick = false } = {}) {
    document.body.classList.remove('tnf-drawer-open');
    document.body.style.overflow = '';
    setDrawerInert(false);
    syncDrawerAria(false);

    if (suppressGhostClick) {
        suppressGhostClickUntil = Date.now() + 500;
    }
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
let drawerTouchConsumed = false;
let suppressGhostClickUntil = 0;

function isNavigableCloser(element) {
    return element instanceof HTMLAnchorElement && element.href !== '' && element.getAttribute('href') !== '#';
}

function resolveDrawerCloseTarget(event) {
    const explicit = event.target.closest('[data-tnf-drawer-close]');

    if (explicit) {
        return explicit;
    }

    if (event.target.closest('.tnf-drawer-overlay')) {
        return event.target.closest('.tnf-drawer-overlay');
    }

    return null;
}

function consumeEvent(event) {
    event.preventDefault();
    event.stopPropagation();
    event.stopImmediatePropagation?.();
}

function handleDrawerClose(event, closer) {
    const navigable = isNavigableCloser(closer);

    closeDrawer({ suppressGhostClick: ! navigable });

    if (! navigable) {
        consumeEvent(event);
        drawerTouchConsumed = true;
    }
}

function handleDrawerClick(event) {
    if (Date.now() < suppressGhostClickUntil) {
        consumeEvent(event);

        return;
    }

    if (event.type === 'click' && Date.now() - drawerLastTouchAt < 600) {
        if (drawerTouchConsumed) {
            consumeEvent(event);
        }

        return;
    }

    const toggle = event.target.closest('[data-tnf-drawer-toggle]');

    if (toggle) {
        consumeEvent(event);
        toggleDrawer();

        return;
    }

    const closer = resolveDrawerCloseTarget(event);

    if (closer && document.body.classList.contains('tnf-drawer-open')) {
        handleDrawerClose(event, closer);
    }
}

function handleDrawerTouchEnd(event) {
    if (Date.now() < suppressGhostClickUntil) {
        consumeEvent(event);

        return;
    }

    drawerTouchConsumed = false;

    const toggle = event.target.closest('[data-tnf-drawer-toggle]');

    if (toggle) {
        drawerLastTouchAt = Date.now();
        consumeEvent(event);
        toggleDrawer();
        drawerTouchConsumed = true;

        return;
    }

    const closer = resolveDrawerCloseTarget(event);

    if (closer && document.body.classList.contains('tnf-drawer-open')) {
        drawerLastTouchAt = Date.now();
        handleDrawerClose(event, closer);
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
            closeDrawer({ suppressGhostClick: true });
        }
    });

    window.tnfToggleDrawer = (event) => {
        event?.preventDefault?.();
        event?.stopPropagation?.();
        toggleDrawer();
    };

    window.tnfCloseDrawer = () => closeDrawer({ suppressGhostClick: true });
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

    // Only reserve status-bar space in installed app / PWA — not regular mobile browser.
    if (isStandalone || isApp) {
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
        const safeTop = parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--tnf-safe-top')) || 0;
        const measured = header.offsetHeight;
        const total = measured > 120 ? 72 : Math.max(measured, 48);
        const chrome = Math.max(total - safeTop, 44);

        document.documentElement.style.setProperty('--tnf-header-chrome', `${chrome}px`);
        document.documentElement.style.setProperty('--tnf-header-total', `${total}px`);
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
    setDrawerInert(false);

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
