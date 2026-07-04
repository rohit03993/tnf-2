const CACHE_NAME = 'tnf-pwa-v6';

const PRECACHE_URLS = [
    '/favicon.svg',
];

function isHtmlRequest(request) {
    if (request.mode === 'navigate') {
        return true;
    }

    const accept = request.headers.get('accept') ?? '';

    return accept.includes('text/html');
}

function isStaticAsset(pathname) {
    return pathname.startsWith('/build/')
        || pathname === '/favicon.svg'
        || pathname === '/apple-touch-icon.svg';
}

function isAlwaysNetwork(pathname) {
    return pathname === '/manifest.json'
        || pathname.startsWith('/pwa/icon/');
}

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => cache.addAll(PRECACHE_URLS))
            .then(() => self.skipWaiting()),
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(
                keys
                    .filter((key) => key !== CACHE_NAME)
                    .map((key) => caches.delete(key)),
            ))
            .then(() => self.clients.claim()),
    );
});

self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') {
        return;
    }

    const url = new URL(event.request.url);

    if (url.origin !== self.location.origin) {
        return;
    }

    // News pages must always come from the network — never serve stale HTML from cache.
    if (isAlwaysNetwork(url.pathname)) {
        event.respondWith(fetch(event.request));

        return;
    }

    if (isHtmlRequest(event.request)) {
        event.respondWith(fetch(event.request));

        return;
    }

    if (! isStaticAsset(url.pathname)) {
        event.respondWith(fetch(event.request));

        return;
    }

    event.respondWith(
        caches.match(event.request).then((cached) => {
            if (cached) {
                return cached;
            }

            return fetch(event.request).then((response) => {
                if (! response || response.status !== 200 || response.type !== 'basic') {
                    return response;
                }

                const copy = response.clone();
                caches.open(CACHE_NAME).then((cache) => cache.put(event.request, copy));

                return response;
            });
        }),
    );
});
