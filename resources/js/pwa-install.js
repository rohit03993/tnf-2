/**
 * TNF Today — PWA install prompts (mobile banner, footer, menu).
 */

const DISMISS_KEY = 'tnf_pwa_install_dismissed_until';
const DISMISS_MS = 14 * 24 * 60 * 60 * 1000;
const BANNER_DELAY_MS = 4000;
const TOAST_MS = 4200;

let deferredPrompt = null;
let serviceWorkerReady = false;

function isStandalone() {
    return window.matchMedia('(display-mode: standalone)').matches
        || window.navigator.standalone === true;
}

function isIos() {
    return /iphone|ipad|ipod/i.test(navigator.userAgent);
}

function isAndroid() {
    return /android/i.test(navigator.userAgent);
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

function canUseAndroidGuide() {
    return isAndroid() && isMobile();
}

function shouldShowInstallUi() {
    if (! canOfferInstall() || isDismissed() || ! isMobile()) {
        return false;
    }

    return canUseNativeInstall() || canUseIosGuide() || canUseAndroidGuide();
}

function showInstallTriggers() {
    document.querySelectorAll('[data-tnf-pwa-install]').forEach((element) => {
        element.hidden = false;
        element.classList.remove('hidden');
        element.removeAttribute('aria-disabled');
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

    if (! banner || ! shouldShowInstallUi()) {
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

function openGuide(modalId) {
    const modal = document.getElementById(modalId);

    if (! modal) {
        return;
    }

    modal.hidden = false;
    modal.classList.add('tnf-pwa-guide--open');
    document.body.classList.add('tnf-pwa-guide-open');
}

function closeGuide(modalId) {
    const modal = document.getElementById(modalId);

    if (! modal) {
        return;
    }

    modal.hidden = true;
    modal.classList.remove('tnf-pwa-guide--open');
    document.body.classList.remove('tnf-pwa-guide-open');
}

function showIosGuide() {
    openGuide('tnf-pwa-ios-guide');
}

function hideIosGuide() {
    closeGuide('tnf-pwa-ios-guide');
}

function showAndroidGuide() {
    openGuide('tnf-pwa-android-guide');
}

function hideAndroidGuide() {
    closeGuide('tnf-pwa-android-guide');
}

function showInstallToast(variant, message) {
    const toast = document.getElementById('tnf-pwa-install-toast');

    if (! toast) {
        return;
    }

    toast.textContent = message;
    toast.dataset.variant = variant;
    toast.hidden = false;
    toast.classList.add('tnf-pwa-install-toast--visible');

    window.clearTimeout(showInstallToast._timer);
    showInstallToast._timer = window.setTimeout(() => {
        toast.hidden = true;
        toast.classList.remove('tnf-pwa-install-toast--visible');
    }, TOAST_MS);
}

function setInstallButtonsBusy(busy) {
    document.querySelectorAll('[data-tnf-pwa-install]').forEach((element) => {
        element.classList.toggle('tnf-pwa-install--busy', busy);
        element.setAttribute('aria-busy', busy ? 'true' : 'false');
    });
}

async function triggerInstall() {
    if (document.body.classList.contains('tnf-drawer-open') && typeof window.tnfCloseDrawer === 'function') {
        window.tnfCloseDrawer();
    }

    if (canUseNativeInstall()) {
        setInstallButtonsBusy(true);
        showInstallToast('progress', 'Opening install prompt…');

        try {
            await deferredPrompt.prompt();
            const choice = await deferredPrompt.userChoice;
            deferredPrompt = null;

            if (choice.outcome === 'accepted') {
                showInstallToast('success', 'Installing TNF Today…');
            } else {
                showInstallToast('info', 'Install cancelled.');
            }
        } catch {
            deferredPrompt = null;
            showInstallToast('error', 'Install prompt unavailable. See manual steps.');
            showAndroidGuide();
        } finally {
            setInstallButtonsBusy(false);
        }

        return;
    }

    if (canUseIosGuide()) {
        showInstallToast('info', 'Follow the steps to add TNF Today.');
        showIosGuide();

        return;
    }

    if (canUseAndroidGuide()) {
        if (! serviceWorkerReady) {
            showInstallToast('progress', 'Preparing app for install…');
            await registerServiceWorker();
        }

        showInstallToast('info', 'Follow the steps to install TNF Today.');
        showAndroidGuide();

        return;
    }

    showInstallToast('error', 'Install is not supported in this browser.');
}

function bindInstallControls() {
    const handler = (event) => {
        event.preventDefault();
        event.stopPropagation();
        triggerInstall();
    };

    document.querySelectorAll('[data-tnf-pwa-install]').forEach((element) => {
        element.addEventListener('click', handler, { capture: true });
        element.addEventListener('touchend', handler, { capture: true, passive: false });
    });

    document.querySelector('[data-tnf-pwa-dismiss]')?.addEventListener('click', () => {
        dismissInstallPrompt();
    });

    document.querySelectorAll('[data-tnf-pwa-ios-close]').forEach((button) => {
        button.addEventListener('click', () => hideIosGuide());
    });

    document.querySelectorAll('[data-tnf-pwa-android-close]').forEach((button) => {
        button.addEventListener('click', () => hideAndroidGuide());
    });

    document.querySelector('#tnf-pwa-ios-guide')?.addEventListener('click', (event) => {
        if (event.target.closest('[data-tnf-pwa-ios-close]')) {
            hideIosGuide();
        }
    });

    document.querySelector('#tnf-pwa-android-guide')?.addEventListener('click', (event) => {
        if (event.target.closest('[data-tnf-pwa-android-close]')) {
            hideAndroidGuide();
        }
    });
}

function scheduleBanner() {
    if (! shouldShowInstallUi()) {
        return;
    }

    window.setTimeout(() => {
        if (shouldShowInstallUi()) {
            showBanner();
        }
    }, BANNER_DELAY_MS);
}

async function registerServiceWorker() {
    if (! ('serviceWorker' in navigator) || isCapacitorApp()) {
        return false;
    }

    try {
        const registration = await navigator.serviceWorker.register('/sw.js');
        await registration.update();
        serviceWorkerReady = true;

        return true;
    } catch {
        serviceWorkerReady = false;

        return false;
    }
}

function initPwaInstall() {
    if (! canOfferInstall()) {
        return;
    }

    bindInstallControls();
    registerServiceWorker();

    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        deferredPrompt = event;
        showInstallTriggers();
        scheduleBanner();
    });

    if (shouldShowInstallUi()) {
        showInstallTriggers();
        scheduleBanner();
    }

    window.addEventListener('appinstalled', () => {
        deferredPrompt = null;
        hideBanner();
        hideInstallTriggers();
        hideAndroidGuide();
        hideIosGuide();
        showInstallToast('success', 'TNF Today installed successfully!');
    });
}

export { initPwaInstall, triggerInstall };
