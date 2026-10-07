const CACHE_NAME = 'trustcash-pwa-v1';
const PRECACHE_ASSETS = [
    '/offline.html',
    '/manifest.webmanifest',
    '/icons/icon-192x192.png',
    '/icons/icon-512x512.png',
    '/icons/favicon-32x32.png',
    '/sounds/beep.mp3',
    '/sounds/success.mp3',
    '/sounds/error.mp3'
];

// Install event - precache core offline assets
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(PRECACHE_ASSETS).catch((err) => {
                console.warn('PWA Precache warning:', err);
            });
        }).then(() => self.skipWaiting())
    );
});

// Activate event - clean up legacy caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cache) => {
                    if (cache !== CACHE_NAME) {
                        return caches.delete(cache);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

// Fetch event
self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);

    // Only handle GET requests
    if (request.method !== 'GET') {
        return;
    }

    // Skip chrome-extension, internal schemes, and socket/poll endpoints
    if (url.protocol !== 'http:' && url.protocol !== 'https:') {
        return;
    }

    // 1. Navigation requests (HTML pages)
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request)
                .then((networkResponse) => {
                    // Cache visited pages
                    if (networkResponse && networkResponse.status === 200) {
                        const responseClone = networkResponse.clone();
                        caches.open(CACHE_NAME).then((cache) => {
                            cache.put(request, responseClone);
                        });
                    }
                    return networkResponse;
                })
                .catch(async () => {
                    // Try cache first
                    const cachedResponse = await caches.match(request);
                    if (cachedResponse) {
                        return cachedResponse;
                    }
                    // Fallback to offline page
                    return caches.match('/offline.html');
                })
        );
        return;
    }

    // 2. Static Assets (CSS, JS, Web Fonts, Images, Audio)
    const isStaticAsset = (
        url.pathname.startsWith('/build/') ||
        url.pathname.startsWith('/icons/') ||
        url.pathname.startsWith('/sounds/') ||
        url.pathname.endsWith('.js') ||
        url.pathname.endsWith('.css') ||
        url.pathname.endsWith('.png') ||
        url.pathname.endsWith('.jpg') ||
        url.pathname.endsWith('.svg') ||
        url.pathname.endsWith('.woff2') ||
        url.hostname.includes('fonts.googleapis.com') ||
        url.hostname.includes('fonts.gstatic.com')
    );

    if (isStaticAsset) {
        event.respondWith(
            caches.match(request).then((cachedResponse) => {
                const fetchPromise = fetch(request).then((networkResponse) => {
                    if (networkResponse && networkResponse.status === 200) {
                        const responseClone = networkResponse.clone();
                        caches.open(CACHE_NAME).then((cache) => {
                            cache.put(request, responseClone);
                        });
                    }
                    return networkResponse;
                }).catch(() => null);

                // Return cached copy immediately (stale-while-revalidate), else wait for network
                return cachedResponse || fetchPromise;
            })
        );
        return;
    }

    // 3. Default network-first for all other requests
    event.respondWith(
        fetch(request).catch(() => caches.match(request))
    );
});
