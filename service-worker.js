importScripts('https://js.pusher.com/beams/service-worker.js');

const STATIC_CACHE = 'qrpay-static-v1';
const RUNTIME_CACHE = 'qrpay-runtime-v1';
const OFFLINE_QUEUE_SYNC_TAG = 'qrpay-offline-sync';
const PRECACHE_URLS = [
    '/',
    '/public/frontend/css/style.css',
    '/public/frontend/js/main.js',
    '/public/frontend/js/offline-manager.js',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE).then((cache) => cache.addAll(PRECACHE_URLS)).then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(
                keys
                    .filter((key) => key !== STATIC_CACHE && key !== RUNTIME_CACHE)
                    .map((key) => caches.delete(key))
            )
        ).then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const { request } = event;

    if (request.method !== 'GET') {
        return;
    }

    const requestURL = new URL(request.url);

    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request)
                .then((response) => {
                    cacheRuntimeResponse(request, response.clone());
                    return response;
                })
                .catch(async () => {
                    const cached = await caches.match(request);

                    if (cached) {
                        return cached;
                    }

                    return new Response('<h1>Offline</h1><p>Please retry once connectivity is restored.</p>', {
                        headers: { 'Content-Type': 'text/html' },
                        status: 503,
                        statusText: 'Service Unavailable',
                    });
                })
        );

        return;
    }

    if (requestURL.origin === self.location.origin) {
        event.respondWith(staleWhileRevalidate(request));
    }
});

self.addEventListener('sync', (event) => {
    if (event.tag === OFFLINE_QUEUE_SYNC_TAG) {
        event.waitUntil(
            self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clients) => {
                clients.forEach((client) => client.postMessage({ type: 'OFFLINE_QUEUE_SYNC' }));
            })
        );
    }
});

self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'REGISTER_BACKGROUND_SYNC') {
        self.registration.sync
            .register(OFFLINE_QUEUE_SYNC_TAG)
            .catch(() => {
                // Background sync may be unavailable; fail silently.
            });
    }
});

function staleWhileRevalidate(request) {
    return caches.match(request).then((cachedResponse) => {
        const fetchPromise = fetch(request)
            .then((networkResponse) => {
                cacheRuntimeResponse(request, networkResponse.clone());
                return networkResponse;
            })
            .catch(() => cachedResponse);

        return cachedResponse || fetchPromise;
    });
}

function cacheRuntimeResponse(request, response) {
    if (!response || response.status !== 200 || response.type !== 'basic') {
        return;
    }

    caches.open(RUNTIME_CACHE).then((cache) => {
        cache.put(request, response);
    });
}
