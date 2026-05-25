/**
 * Cool AgriStock — Service Worker
 * Scope: / (all pages)
 *
 * Strategies:
 *  • Static assets (.css, .js, fonts, images) → Cache-First, network fallback
 *  • HTML pages                               → Network-First, cache fallback
 *  • /api/* and /sanctum/*                    → Network-only (never cache)
 *
 * Background Sync:
 *  • 'inventory-sync' tag → messages all open clients so the page-level
 *    sync module can flush its pending-ops queue via POST /api/sync/push.
 */

const CACHE_NAME    = 'agristock-v1';
const OFFLINE_PAGE  = '/offline.html';

/** Assets pre-cached on install (app shell). */
const SHELL_ASSETS = [
    '/css/bootstrap.min.css',
    '/css/icons.min.css',
    '/css/app.min.css',
    '/js/app.js',
    '/images/logo.png',
    '/images/favicon.ico',
    '/favicon.ico',
];

// ── Install ───────────────────────────────────────────────────────────────

self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => cache.addAll(SHELL_ASSETS.filter(Boolean)))
            .then(() => self.skipWaiting())
            .catch(err => {
                // Non-fatal: individual asset misses shouldn't abort install
                console.warn('[SW] Shell pre-cache partial failure:', err);
                return self.skipWaiting();
            })
    );
});

// ── Activate ──────────────────────────────────────────────────────────────

self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys()
            .then(keys => Promise.all(
                keys
                    .filter(key => key !== CACHE_NAME)
                    .map(key => caches.delete(key))
            ))
            .then(() => self.clients.claim())
    );
});

// ── Fetch ─────────────────────────────────────────────────────────────────

self.addEventListener('fetch', event => {
    const { request } = event;
    const url = new URL(request.url);

    // Only intercept same-origin requests
    if (url.origin !== self.location.origin) return;

    // Never cache API / auth / CSRF endpoints
    if (
        url.pathname.startsWith('/api/')    ||
        url.pathname.startsWith('/sanctum/') ||
        url.pathname.startsWith('/login')   ||
        url.pathname.startsWith('/logout')
    ) {
        return; // fall through to network
    }

    // Static assets — cache-first
    if (isStaticAsset(request)) {
        event.respondWith(cacheFirst(request));
        return;
    }

    // HTML pages — network-first with cache fallback
    if (request.headers.get('Accept')?.includes('text/html')) {
        event.respondWith(networkFirst(request));
        return;
    }
});

function isStaticAsset(request) {
    return (
        request.destination === 'style'  ||
        request.destination === 'script' ||
        request.destination === 'font'   ||
        request.destination === 'image'
    );
}

async function cacheFirst(request) {
    const cached = await caches.match(request);
    if (cached) return cached;
    try {
        const response = await fetch(request);
        if (response.ok) {
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, response.clone());
        }
        return response;
    } catch {
        return new Response('', { status: 503 });
    }
}

async function networkFirst(request) {
    try {
        const response = await fetch(request);
        if (response.ok) {
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, response.clone());
        }
        return response;
    } catch {
        const cached = await caches.match(request);
        if (cached) return cached;
        // Return the cached offline page if available, or a bare 503
        const offlineFallback = await caches.match(OFFLINE_PAGE);
        return offlineFallback || new Response(
            '<h1>Offline</h1><p>Please check your connection.</p>',
            { status: 503, headers: { 'Content-Type': 'text/html' } }
        );
    }
}

// ── Background Sync ───────────────────────────────────────────────────────

self.addEventListener('sync', event => {
    if (event.tag === 'inventory-sync') {
        event.waitUntil(notifyClientsToSync());
    }
});

async function notifyClientsToSync() {
    const clients = await self.clients.matchAll({
        includeUncontrolled: true,
        type: 'window',
    });
    clients.forEach(client =>
        client.postMessage({ type: 'SYNC_REQUESTED', tag: 'inventory-sync' })
    );
}

// ── Message handler ───────────────────────────────────────────────────────
// Allows pages to ping the SW to manually trigger a client notification.

self.addEventListener('message', event => {
    if (event.data?.type === 'PING') {
        event.source?.postMessage({ type: 'PONG' });
    }
});
