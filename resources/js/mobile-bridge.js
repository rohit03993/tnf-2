/**
 * TNF Today — Capacitor / app-mode bridge (Phase L)
 */

(function () {
    const body = document.body;
    if (!body || body.dataset.tnfApp !== '1') {
        return;
    }

    const loader = document.getElementById('tnf-page-loader');
    const offlineOverlay = document.getElementById('tnf-offline-overlay');

    const isNative = () =>
        Boolean(window.Capacitor?.isNativePlatform?.()) ||
        navigator.userAgent.includes('TNFTodayCapacitor');

    const isQaMode = () =>
        document.cookie.includes('tnf_app=1') && !isNative();

    const showLoader = () => loader?.classList.remove('hidden');
    const hideLoader = () => loader?.classList.add('hidden');
    const showOffline = () => offlineOverlay?.classList.remove('hidden');
    const hideOffline = () => offlineOverlay?.classList.add('hidden');

    async function hapticLight() {
        try {
            const Haptics = window.Capacitor?.Plugins?.Haptics;
            if (Haptics) {
                await Haptics.impact({ style: 'LIGHT' });
            }
        } catch {
            // Native haptics unavailable — ignore
        }
    }

    async function openInBrowser(url) {
        try {
            const Browser = window.Capacitor?.Plugins?.Browser;
            if (Browser && isNative()) {
                await Browser.open({ url });
                return true;
            }
        } catch {
            // Fall through to default browser behavior
        }

        return false;
    }

    function isInternalUrl(href) {
        if (!href || href.startsWith('#') || href.startsWith('javascript:')) {
            return false;
        }

        try {
            const url = new URL(href, window.location.origin);
            return url.origin === window.location.origin;
        } catch {
            return false;
        }
    }

    function withAppQuery(href) {
        if (!isQaMode() || href.includes('tnf_app=1')) {
            return href;
        }

        const url = new URL(href, window.location.origin);
        url.searchParams.set('tnf_app', '1');

        return url.pathname + url.search + url.hash;
    }

    function preserveAppQueryOnLinks() {
        if (!isQaMode()) {
            return;
        }

        document.querySelectorAll('a[href]').forEach((anchor) => {
            const href = anchor.getAttribute('href');
            if (!href || !isInternalUrl(href)) {
                return;
            }

            anchor.setAttribute('href', withAppQuery(href));
        });
    }

    function shouldOpenExternally(anchor, href) {
        if (anchor.dataset.tnfExternal === '1') {
            return true;
        }

        if (anchor.target === '_blank') {
            return true;
        }

        return href && !isInternalUrl(href);
    }

    function bindNavigationLoader() {
        document.addEventListener('click', async (event) => {
            const anchor = event.target.closest('a[href]');
            if (!anchor) {
                return;
            }

            const href = anchor.getAttribute('href');
            if (!href || href.startsWith('#')) {
                return;
            }

            if (anchor.closest('.tnf-app-tab-bar')) {
                void hapticLight();
            }

            if (shouldOpenExternally(anchor, href)) {
                if (isNative()) {
                    event.preventDefault();
                    const opened = await openInBrowser(anchor.href);
                    if (!opened) {
                        window.open(anchor.href, '_blank', 'noopener,noreferrer');
                    }
                }

                return;
            }

            try {
                const dest = new URL(anchor.href);
                const current = new URL(window.location.href);
                if (dest.pathname === current.pathname && dest.search === current.search) {
                    return;
                }
            } catch {
                return;
            }

            showLoader();
        }, true);

        window.addEventListener('pageshow', () => {
            hideLoader();
            preserveAppQueryOnLinks();
        });

        hideLoader();
    }

    function bindOffline() {
        const update = () => {
            if (navigator.onLine) {
                hideOffline();
            } else {
                showOffline();
                hideLoader();
            }
        };

        window.addEventListener('online', update);
        window.addEventListener('offline', update);
        update();

        document.getElementById('tnf-offline-retry')?.addEventListener('click', () => {
            if (navigator.onLine) {
                hideOffline();
                location.reload();
            } else {
                showOffline();
            }
        });
    }

    function bindPullToRefresh() {
        if (body.dataset.tnfHome !== '1') {
            return;
        }

        let startY = 0;
        let pulling = false;
        const threshold = 72;

        document.addEventListener('touchstart', (event) => {
            if (window.scrollY > 0) {
                return;
            }

            startY = event.touches[0].clientY;
            pulling = true;
        }, { passive: true });

        document.addEventListener('touchmove', (event) => {
            if (!pulling || window.scrollY > 0) {
                return;
            }

            const delta = event.touches[0].clientY - startY;
            body.classList.toggle('tnf-ptr-ready', delta > threshold);
        }, { passive: true });

        document.addEventListener('touchend', () => {
            if (!pulling) {
                return;
            }

            pulling = false;

            if (body.classList.contains('tnf-ptr-ready')) {
                body.classList.remove('tnf-ptr-ready');
                showLoader();
                void hapticLight();
                location.reload();
            }
        }, { passive: true });
    }

    document.querySelector('.tnf-app-tab-bar')?.addEventListener('click', (event) => {
        if (event.target.closest('button')) {
            void hapticLight();
        }
    });

    preserveAppQueryOnLinks();
    bindNavigationLoader();
    bindOffline();
    bindPullToRefresh();
})();
