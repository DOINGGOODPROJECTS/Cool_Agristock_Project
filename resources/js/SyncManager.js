/**
 * SyncManager — client-side offline sync layer for Cool AgriStock
 *
 * IndexedDB schema (agristock_sync v1):
 *   pending_ops      keyPath: 'op_id'               — ops queued for push
 *   sync_meta        keyPath: 'key'                 — logical_seq, last_sync_at, …
 *   inventory_stock  keyPath: ['storage_id','product_id'] — local qty cache
 *
 * Public API:
 *   syncManager.init()                  — must be called once on boot
 *   syncManager.pull()                  — fetch remote state from server
 *   syncManager.recordOp(opData)        — queue a new op + optimistic update
 *   syncManager.sync()                  — flush pending queue to server
 *   syncManager.cancelOp(opId)          — remove local + notify server
 *   syncManager.editOp(opId, changes)   — update local + notify server
 *
 * DOM events dispatched:
 *   sync:completed      { applied, conflicts, already_seen }
 *   sync:conflicts      { conflicts[] }
 *   sync:error          { error }
 *   sync:unauthorized   {}
 *   pull:completed      { remote_ops }
 *   agristock:connectivity-changed  { online }  (from app.js)
 */

const DB_NAME    = 'agristock_sync';
const DB_VERSION = 1;

class SyncManager {
    // Private fields
    #db         = null;
    #deviceId   = null;
    #userId     = null;
    #logicalSeq = 0;
    #isSyncing  = false;

    // ── Boot ─────────────────────────────────────────────────────────────

    /**
     * Open IndexedDB, restore Lamport clock, wire listeners.
     * Call once on app boot before any other method.
     */
    async init() {
        this.#db         = await this.#openDB();
        this.#deviceId   = this.#getOrCreateDeviceId();
        this.#userId     = this.#getUserId();
        this.#logicalSeq = await this.#loadLogicalSeq();

        // Sync when connectivity is restored
        window.addEventListener('online', () => this.sync());

        // Sync when the service worker fires the background-sync event
        window.addEventListener('agristock:sync-requested', () => this.sync());

        return this;
    }

    // ── recordOp ─────────────────────────────────────────────────────────

    /**
     * Queue a new inventory operation.
     *
     *  1. Increment Lamport clock
     *  2. Generate UUID op_id
     *  3. Persist to pending_ops IDB store
     *  4. Optimistically update local inventory_stock
     *  5. Trigger sync() if online
     *
     * @param {object} opData
     *   Required: storage_id, product_id, op_type, quantity_delta, unit
     *   Optional: stock_id, notes
     * @returns {string} op_id of the queued op
     */
    async recordOp(opData) {
        // ── 1. Lamport clock ─────────────────────────────────────────────
        this.#logicalSeq += 1;
        await this.#saveLogicalSeq(this.#logicalSeq);

        // ── 2. Build the op record ────────────────────────────────────────
        const op = {
            op_id:             crypto.randomUUID(),
            user_id:           this.#userId,
            device_id:         this.#deviceId,
            logical_seq:       this.#logicalSeq,
            storage_id:        opData.storage_id,
            product_id:        opData.product_id,
            stock_id:          opData.stock_id   ?? null,
            op_type:           opData.op_type,
            quantity_delta:    opData.quantity_delta,
            unit:              opData.unit         ?? 'kg',
            notes:             opData.notes        ?? null,
            client_created_at: new Date().toISOString(),
        };

        // ── 3. Persist to pending queue ───────────────────────────────────
        await this.#idbPut('pending_ops', op);

        // ── 4. Optimistic local update ────────────────────────────────────
        await this.#applyOpLocally(op, /* optimistic */ true);

        // ── 5. Try to flush now if we have network ────────────────────────
        if (navigator.onLine) this.sync();

        return op.op_id;
    }

    // ── sync ──────────────────────────────────────────────────────────────

    /**
     * Push all pending ops to POST /api/sync/push.
     *
     * On success:
     *   - Advances Lamport clock to max(local, server_logical_seq)
     *   - Replaces optimistic inventory_stock entries with authoritative_state
     *   - Removes all pushed ops from pending_ops (conflicts included — they
     *     are now server-side and resolved via the web UI)
     *   - Updates last_sync_at
     *   - Dispatches sync:completed and (if any) sync:conflicts
     *
     * On failure: leaves queue intact; will retry on next online/SW event.
     */
    async sync() {
        if (this.#isSyncing || !navigator.onLine) return;
        this.#isSyncing = true;

        try {
            const ops = await this.#getAllPending();
            if (ops.length === 0) return;

            const lastSyncAt = await this.#getMeta('last_sync_at');
            const sessionId  = crypto.randomUUID();

            const response = await this.#apiPost('/api/sync/push', {
                session_id:   sessionId,
                device_id:    this.#deviceId,
                last_sync_at: lastSyncAt,
                ops,
            });

            // ── Advance Lamport clock ─────────────────────────────────────
            const serverSeq = response.server_logical_seq ?? 0;
            if (serverSeq > this.#logicalSeq) {
                this.#logicalSeq = serverSeq;
                await this.#saveLogicalSeq(this.#logicalSeq);
            }

            // ── Apply authoritative stock quantities ──────────────────────
            for (const entry of (response.authoritative_state ?? [])) {
                await this.#idbPut('inventory_stock', {
                    storage_id: entry.storage_id,
                    product_id: entry.product_id,
                    quantity:   entry.quantity,
                    unit:       entry.unit ?? 'kg',
                    optimistic: false,
                });
            }

            // ── Remove pushed ops from queue ──────────────────────────────
            // All sent ops are now acknowledged (applied, already_seen, or
            // conflict). Conflicts live on the server with sync_status='conflict'
            // and are resolved via /inventory-ops in the web UI.
            for (const op of ops) {
                await this.#idbDelete('pending_ops', op.op_id);
            }

            // ── Persist metadata ──────────────────────────────────────────
            await this.#saveMeta('last_sync_at', new Date().toISOString());
            await this.#saveMeta('server_logical_seq', serverSeq);

            // ── Dispatch events ───────────────────────────────────────────
            if ((response.conflicts ?? []).length > 0) {
                window.dispatchEvent(new CustomEvent('sync:conflicts', {
                    detail: { conflicts: response.conflicts, sessionId },
                }));
            }

            window.dispatchEvent(new CustomEvent('sync:completed', {
                detail: {
                    applied:      response.applied_count      ?? 0,
                    conflicts:    response.conflict_count     ?? 0,
                    already_seen: response.already_seen_count ?? 0,
                },
            }));
        } catch (err) {
            console.error('[SyncManager] sync() failed:', err);
            window.dispatchEvent(new CustomEvent('sync:error', {
                detail: { error: err.message },
            }));
        } finally {
            this.#isSyncing = false;
        }
    }

    // ── pull ──────────────────────────────────────────────────────────────

    /**
     * Fetch ops applied by other devices and pending conflicts from server.
     * Advances Lamport clock to max(local, server_logical_seq).
     * Applies remote ops to local inventory_stock.
     */
    async pull() {
        if (!navigator.onLine) return;

        try {
            const serverSeq = (await this.#getMeta('server_logical_seq')) ?? 0;

            const data = await this.#apiGet('/api/sync/pull', {
                since_logical_seq: serverSeq,
                device_id:         this.#deviceId,
            });

            // Advance clock
            const newServerSeq = data.server_logical_seq ?? 0;
            if (newServerSeq > this.#logicalSeq) {
                this.#logicalSeq = newServerSeq;
                await this.#saveLogicalSeq(this.#logicalSeq);
            }
            await this.#saveMeta('server_logical_seq', newServerSeq);

            // Apply remote applied ops to local stock cache
            for (const op of (data.remote_ops ?? [])) {
                await this.#applyOpLocally(op, /* optimistic */ false);
            }

            // Surface pending conflicts so the UI can prompt the user
            if ((data.pending_conflicts ?? []).length > 0) {
                window.dispatchEvent(new CustomEvent('sync:conflicts', {
                    detail: { conflicts: data.pending_conflicts },
                }));
            }

            window.dispatchEvent(new CustomEvent('pull:completed', {
                detail: { remote_ops: data.remote_ops?.length ?? 0 },
            }));
        } catch (err) {
            console.error('[SyncManager] pull() failed:', err);
        }
    }

    // ── cancelOp ─────────────────────────────────────────────────────────

    /**
     * Remove an op from the local queue and tell the server to cancel it.
     * If offline, the server call is skipped; the op is removed locally so it
     * won't be pushed on the next sync (prevents a stale push after cancel).
     *
     * @param {string} opId
     * @param {string} [reason]  — optional cancellation note surfaced in the audit log
     */
    async cancelOp(opId, reason = '') {
        await this.#idbDelete('pending_ops', opId);

        if (navigator.onLine) {
            await this.#apiPost('/api/sync/cancel', { op_id: opId, reason: reason || undefined })
                .catch(err => console.warn('[SyncManager] cancelOp server call failed:', err));
        }
    }

    // ── editOp ────────────────────────────────────────────────────────────

    /**
     * Update a pending op locally and notify the server.
     * The server creates a replacement pending op (editOp engine method).
     * Locally we patch the queued entry so the next push sends the corrected
     * values.
     *
     * @param {string} opId
     * @param {object} changes  — { quantity_delta?, notes?, reason? }
     */
    async editOp(opId, changes) {
        const existing = await this.#idbGet('pending_ops', opId);
        if (existing) {
            // Patch only the fields the server allows editing
            const patched = {
                ...existing,
                ...(changes.quantity_delta !== undefined && { quantity_delta: changes.quantity_delta }),
                ...(changes.notes          !== undefined && { notes:          changes.notes }),
            };
            await this.#idbPut('pending_ops', patched);
        }

        if (navigator.onLine) {
            await this.#apiPost('/api/sync/edit', { op_id: opId, changes })
                .catch(err => console.warn('[SyncManager] editOp server call failed:', err));
        }
    }

    // ── Querying helpers (public read-only access for UI) ─────────────────

    /** Returns all pending ops, sorted by logical_seq ascending. */
    async getPendingOps() {
        return this.#getAllPending();
    }

    /** Returns the cached local quantity for a (storage_id, product_id) pair. */
    async getLocalQty(storageId, productId) {
        const row = await this.#idbGet('inventory_stock', [storageId, productId]);
        return row ?? null;
    }

    // ── IndexedDB setup ───────────────────────────────────────────────────

    #openDB() {
        return new Promise((resolve, reject) => {
            const req = indexedDB.open(DB_NAME, DB_VERSION);

            req.onupgradeneeded = e => {
                const db = e.target.result;

                // Pending operation queue
                if (!db.objectStoreNames.contains('pending_ops')) {
                    const ops = db.createObjectStore('pending_ops', { keyPath: 'op_id' });
                    // Index on logical_seq so getAll() returns causally ordered ops
                    ops.createIndex('logical_seq', 'logical_seq', { unique: false });
                }

                // Sync metadata: logical_seq, last_sync_at, server_logical_seq
                if (!db.objectStoreNames.contains('sync_meta')) {
                    db.createObjectStore('sync_meta', { keyPath: 'key' });
                }

                // Local inventory stock cache
                // Compound key matches the server's unique constraint:
                //   (storage_id, product_id) — used for optimistic qty display
                if (!db.objectStoreNames.contains('inventory_stock')) {
                    db.createObjectStore('inventory_stock', {
                        keyPath: ['storage_id', 'product_id'],
                    });
                }
            };

            req.onsuccess = e => resolve(e.target.result);
            req.onerror   = e => reject(e.target.error);
        });
    }

    // ── Device identity ───────────────────────────────────────────────────

    #getOrCreateDeviceId() {
        const LS_KEY   = 'agristock_device_id';
        let   deviceId = localStorage.getItem(LS_KEY);
        if (!deviceId) {
            deviceId = `web-${crypto.randomUUID()}`;
            localStorage.setItem(LS_KEY, deviceId);
        }
        return deviceId;
    }

    #getUserId() {
        const meta = document.querySelector('meta[name="user-id"]');
        return meta ? parseInt(meta.content, 10) : null;
    }

    // ── Lamport clock ─────────────────────────────────────────────────────

    async #loadLogicalSeq() {
        return (await this.#getMeta('logical_seq')) ?? 0;
    }

    async #saveLogicalSeq(seq) {
        await this.#saveMeta('logical_seq', seq);
    }

    // ── Local stock application ───────────────────────────────────────────

    async #applyOpLocally(op, optimistic = true) {
        const key      = [op.storage_id, op.product_id];
        const existing = await this.#idbGet('inventory_stock', key);
        const current  = parseFloat(existing?.quantity ?? 0);
        const delta    = parseFloat(op.quantity_delta)  || 0;

        await this.#idbPut('inventory_stock', {
            storage_id: op.storage_id,
            product_id: op.product_id,
            quantity:   current + delta,
            unit:       op.unit ?? existing?.unit ?? 'kg',
            optimistic,
        });
    }

    // ── IDB helpers ───────────────────────────────────────────────────────

    #idbGet(storeName, key) {
        return new Promise((resolve, reject) => {
            const tx  = this.#db.transaction(storeName, 'readonly');
            const req = tx.objectStore(storeName).get(key);
            req.onsuccess = () => resolve(req.result ?? null);
            req.onerror   = () => reject(req.error);
        });
    }

    #idbPut(storeName, value) {
        return new Promise((resolve, reject) => {
            const tx  = this.#db.transaction(storeName, 'readwrite');
            const req = tx.objectStore(storeName).put(value);
            req.onsuccess = () => resolve(req.result);
            req.onerror   = () => reject(req.error);
        });
    }

    #idbDelete(storeName, key) {
        return new Promise((resolve, reject) => {
            const tx  = this.#db.transaction(storeName, 'readwrite');
            const req = tx.objectStore(storeName).delete(key);
            req.onsuccess = () => resolve();
            req.onerror   = () => reject(req.error);
        });
    }

    #getAllPending() {
        return new Promise((resolve, reject) => {
            const tx    = this.#db.transaction('pending_ops', 'readonly');
            const index = tx.objectStore('pending_ops').index('logical_seq');
            // IDB index.getAll() returns records in ascending key (logical_seq) order
            const req   = index.getAll();
            req.onsuccess = () => resolve(req.result ?? []);
            req.onerror   = () => reject(req.error);
        });
    }

    async #getMeta(key) {
        const row = await this.#idbGet('sync_meta', key);
        return row?.value ?? null;
    }

    async #saveMeta(key, value) {
        await this.#idbPut('sync_meta', { key, value });
    }

    // ── HTTP helpers ──────────────────────────────────────────────────────

    #csrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    }

    async #apiPost(path, body) {
        const res = await fetch(path, {
            method:      'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type':     'application/json',
                'Accept':           'application/json',
                'X-CSRF-TOKEN':     this.#csrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(body),
        });

        if (res.status === 401) {
            window.dispatchEvent(new CustomEvent('sync:unauthorized'));
            throw new Error('Unauthenticated — session may have expired');
        }

        if (!res.ok) {
            const payload = await res.json().catch(() => ({}));
            throw new Error(payload.message ?? `HTTP ${res.status}`);
        }

        return res.json();
    }

    async #apiGet(path, params = {}) {
        const qs  = new URLSearchParams(
            Object.fromEntries(Object.entries(params).filter(([, v]) => v !== null && v !== undefined))
        ).toString();
        const url = qs ? `${path}?${qs}` : path;

        const res = await fetch(url, {
            method:      'GET',
            credentials: 'same-origin',
            headers: {
                'Accept':           'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (res.status === 401) {
            window.dispatchEvent(new CustomEvent('sync:unauthorized'));
            throw new Error('Unauthenticated');
        }

        if (!res.ok) throw new Error(`HTTP ${res.status}`);

        return res.json();
    }
}

// Export singleton — import { syncManager } from './SyncManager'
export const syncManager = new SyncManager();
