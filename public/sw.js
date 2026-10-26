/*
 * BMPC Project Management service worker.
 *
 * Makes the system installable as an app. Pages are always fetched live from
 * the server; nothing with project or personal data is stored on the device.
 * Only a small offline page and the app icons are kept, so a lost connection
 * shows a clear message instead of the browser's error screen.
 */
const CACHE = 'bmpc-shell-v2';
const OFFLINE_URL = '/offline.html';
const PRECACHE = [OFFLINE_URL, '/icons/icon-192.png'];

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(CACHE).then((cache) => cache.addAll(PRECACHE)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((key) => key !== CACHE).map((key) => caches.delete(key))))
            .then(() => self.clients.claim()),
    );
});

self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // Full page loads: always live, with the offline page if there's no connection.
    if (request.mode === 'navigate') {
        event.respondWith(fetch(request).catch(() => caches.match(OFFLINE_URL)));
        return;
    }

    // The offline page's own logo, served from the device when offline.
    if (url.origin === self.location.origin && PRECACHE.includes(url.pathname)) {
        event.respondWith(fetch(request).catch(() => caches.match(url.pathname)));
    }

    // Everything else goes straight to the network, untouched.
});
