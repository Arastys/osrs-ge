const CACHE_NAME = 'osrs-ge-tracker-v1';
const ASSETS_TO_CACHE = [
    '/dashboard',
    '/icon-512.png',
    '/manifest.json'
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(ASSETS_TO_CACHE);
        })
    );
});

self.addEventListener('fetch', (event) => {
    // Skip non-GET requests or requests to cross-origin APIs if needed
    if (event.request.method !== 'GET') return;

    event.respondWith(
        fetch(event.request)
            .then((response) => {
                // If network fetch is successful, return it
                return response;
            })
            .catch(() => {
                // If network fails (offline or SSL error), try cache
                return caches.match(event.request);
            })
    );
});
