/**
 * GoniCore Offline Service Worker
 * ────────────────────────────────────────────────────────────
 * Serves the engine's own "no connection" page (offline.html)
 * instead of the browser's default error page whenever a
 * navigation request fails — on the site and the admin panel.
 *
 * Scope-relative: works whether the app lives at the domain
 * root or in a sub-directory.
 */

const CACHE_NAME  = 'gc-offline-v1';
const OFFLINE_URL = new URL('offline.html', self.registration.scope).href;

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            // cache: 'reload' bypasses the HTTP cache so we always store a fresh copy
            .then((cache) => cache.add(new Request(OFFLINE_URL, { cache: 'reload' })))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(
                keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k))
            ))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    // Only intercept full page navigations — never API/asset requests,
    // so normal app behaviour (and its own error handling) is untouched.
    if (event.request.mode !== 'navigate') return;

    event.respondWith(
        fetch(event.request).catch(() =>
            caches.open(CACHE_NAME)
                .then((cache) => cache.match(OFFLINE_URL))
                .then((cached) => cached || Response.error())
        )
    );
});
