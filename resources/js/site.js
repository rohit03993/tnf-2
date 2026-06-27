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
}

export function closeDrawer() {
    document.body.classList.remove('tnf-drawer-open');
    document.body.style.overflow = '';
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

export function initSiteUi() {
    document.body.classList.remove('tnf-drawer-open');
    document.body.style.overflow = '';

    initDrawer();
    initBackToTop();
    initShareButtons();
}

document.addEventListener('alpine:init', () => {
    if (window.Alpine) {
        registerTnfSite(window.Alpine);
    }
});
