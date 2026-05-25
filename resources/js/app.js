import './bootstrap';

import Alpine from 'alpinejs';
window.Alpine = Alpine;
Alpine.start();

import { syncManager } from './SyncManager';
window.syncManager = syncManager;
document.addEventListener('DOMContentLoaded', () =>
    syncManager.init().then(() => syncManager.pull())
);

// ── Service Worker registration ───────────────────────────────────────────

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker
            .register('/sw.js', { scope: '/' })
            .then(registration => {
                // Re-register Background Sync tag so it fires when
                // connectivity is restored after an offline session.
                if ('SyncManager' in window) {
                    navigator.serviceWorker.ready.then(sw =>
                        sw.sync.register('inventory-sync')
                    );
                }
            })
            .catch(err => console.error('[SW] Registration failed:', err));

        // Receive SYNC_REQUESTED from the service worker's sync event handler
        // and re-broadcast as a DOM event that the sync module can listen to.
        navigator.serviceWorker.addEventListener('message', event => {
            if (event.data?.type === 'SYNC_REQUESTED') {
                window.dispatchEvent(
                    new CustomEvent('agristock:sync-requested', {
                        detail: { tag: event.data.tag },
                    })
                );
            }
        });
    });
}

// ── Connectivity indicator ────────────────────────────────────────────────

function setConnectivity(isOnline) {
    const pill = document.getElementById('connectivity-status');
    if (!pill) return;

    if (isOnline) {
        pill.className  = 'badge rounded-pill bg-success-subtle text-success border border-success-subtle px-2 py-1';
        pill.innerHTML  = '<i class="fas fa-wifi me-1"></i><span class="d-none d-sm-inline">Online</span>';
        pill.title      = 'Connected';
    } else {
        pill.className  = 'badge rounded-pill bg-danger-subtle text-danger border border-danger-subtle px-2 py-1';
        pill.innerHTML  = '<i class="fas fa-wifi me-1"></i><span class="d-none d-sm-inline">Offline</span>';
        pill.title      = 'No network connection';
    }

    // Notify other listeners (e.g. the sync queue) of the state change
    window.dispatchEvent(
        new CustomEvent('agristock:connectivity-changed', { detail: { online: isOnline } })
    );
}

window.addEventListener('online',  () => setConnectivity(true));
window.addEventListener('offline', () => setConnectivity(false));

// Set initial state as soon as the DOM is ready
document.addEventListener('DOMContentLoaded', () => setConnectivity(navigator.onLine));
