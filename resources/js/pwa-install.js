/**
 * TNF Today — PWA install prompts (mobile banner, footer, menu).
 */

const DISMISS_KEY = 'tnf_pwa_install_dismissed_until';
const DISMISS_MS = 14 * 24 * 60 * 60 * 1000;
const BANNER_DELAY_MS = 4000;

let deferredPrompt = null;

function isStandalone() {
    return window.matchMedia('(display-mode: standalone)').matches
        || window.navigator.standalone === true;
}

function isIos() {
    return /iphone|ipad|ipod/i.test(navigator.userAgent);
}

function isMobile() {
    return window.matchMedia('(max-width: 1023px)').matches;
}

function isCapacitorApp() {
    return document.body.dataset.tnfApp === '1'
        || navigator.userAgent.includes('TNFTodayCapacitor');
}

function isDismissed() {
    const until = localStorage.getItem(DISMISS_KEY);

    return until !== null && Date.now() < Number(until);
}

function dismissInstallPrompt() {
    localStorage.setItem(DISMISS_KEY, String(Date.now() + DISMISS_MS));
    hideBanner();
}

function canOfferInstall() {
    return ! isStandalone() && ! isCapacitorApp();
}

function canUseNativeInstall() {
    return deferredPrompt !== null;
}

function canUseIosGuide() {
    return isIos() && isMobile();
}

function shouldShowInstallUi() {
    if (! canOfferInstall() || isDismissed()) {
        return false;
    }

    return canUseNativeInstall() || canUseIosGuide();
}

function showInstallTriggers() {
    document.querySelectorAll('[data-tnf-pwa-install]').forEach((element) => {
        element.hidden = false;
        element.classList.remove('hidden');
    });
}

function hideInstallTriggers() {
    document.querySelectorAll('[data-tnf-pwa-install]').forEach((element) => {
        element.hidden = true;
    });
}

function hideBanner() {
    const banner = document.getElementById('tnf-pwa-install-banner');

    if (banner) {
        banner.hidden = true;
        banner.classList.add('tnf-pwa-install-banner--hidden');
    }
}

function showBanner() {
    const banner = document.getElementById('tnf-pwa-install-banner');

    if (! banner || ! isMobile() || ! shouldShowInstallUi()) {
        return;
    }

    const androidCopy = banner.querySelector('[data-tnf-pwa-android-copy]');
    const iosCopy = banner.querySelector('[data-tnf-pwa-ios-copy]');

    if (isIos()) {
        androidCopy?.classList.add('hidden');
        iosCopy?.classList.remove('hidden');
    } else {
        androidCopy?.classList.remove('hidden');
        iosCopy?.classList.add('hidden');
    }

    banner.hidden = false;
    banner.classList.remove('tnf-pwa-install-banner--hidden');
}

function showIosGuide() {
    const modal = document.getElementById('tnf-pwa-ios-guide');

    if (! modal) {
        return;
    }

    modal.hidden = false;
    modal.classList.add('tnf-pwa-ios-guide--open');
    document.body.classList.add('tnf-pwa-ios-guide-open');
}

function hideIosGuide() {
    const modal = document.getElementById('tnf-pwa-ios-guide');

    if (! modal) {
        return;
    }

    modal.hidden = true;
    modal.classList.remove('tnf-pwa-ios-guide--open');
    document.body.classList.remove('tnf-pwa-ios-guide-open');
}

async function triggerInstall() {
    if (document.body.classList.contains('tnf-drawer-open') && typeof window.tnfCloseDrawer === 'function') {
        window.tnfCloseDrawer();
    }

    if (canUseNativeInstall()) {
        deferredPrompt.prompt();

        const choice = await deferredPrompt.userChoice;

        deferredPrompt = null;

        if (choice.outcome === 'accepted') {
            hideBanner();
            hideInstallTriggers();
        }

        return;
    }

    if (canUseIosGuide()) {
        showIosGuide();
    }
}

function bindInstallControls() {
    document.querySelectorAll('[data-tnf-pwa-install]').forEach((element) => {
        element.addEventListener('click', (event) => {
            event.preventDefault();
            triggerInstall();
        });
    });

    document.querySelector('[data-tnf-pwa-dismiss]')?.addEventListener('click', () => {
        dismissInstallPrompt();
    });

    document.querySelector('[data-tnf-pwa-ios-close]')?.addEventListener('click', () => {
        hideIosGuide();
    });

    document.querySelector('#tnf-pwa-ios-guide')?.addEventListener('click', (event) => {
        if (event.target.closest('[data-tnf-pwa-ios-close]')) {
            hideIosGuide();
        }
    });
}

function scheduleBanner() {
    if (! isMobile() || ! shouldShowInstallUi()) {
        return;
    }

    window.setTimeout(() => {
        if (shouldShowInstallUi()) {
            showBanner();
        }
    }, BANNER_DELAY_MS);
}

function registerServiceWorker() {
    if (! ('serviceWorker' in navigator) || isCapacitorApp()) {
        return;
    }

    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {});
    });
}

function initPwaInstall() {
    if (! canOfferInstall()) {
        return;
    }

    registerServiceWorker();
    bindInstallControls();

    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        deferredPrompt = event;

        if (shouldShowInstallUi()) {
            showInstallTriggers();
            scheduleBanner();
        }
    });

    if (canUseIosGuide() && ! isDismissed()) {
        showInstallTriggers();
        scheduleBanner();
    }

    window.addEventListener('appinstalled', () => {
        deferredPrompt = null;
        hideBanner();
        hideInstallTriggers();
    });
}

export { initPwaInstall, triggerInstall };
