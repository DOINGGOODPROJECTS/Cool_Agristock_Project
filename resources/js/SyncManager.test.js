/**
 * S12-24 — offline flow end-to-end tests for SyncManager
 *
 * Coverage:
 *   S12-03/S12-11  recordOp stores op in pending_ops (offline)
 *   S12-12         Optimistic inventory_stock update
 *   S12-09         Lamport clock increments monotonically
 *   S12-13/S12-22  sync not triggered offline; triggers on 'online' event
 *   S12-14/S12-15/S12-16/S12-17  sync() pushes ops, drains queue, applies authoritative state
 *   S12-17         sync:completed event dispatched
 *   S12-18         sync() leaves queue intact on failure
 *   S12-19         pull() applies remote ops, advances clock
 *   S12-20         cancelOp removes from queue; POSTs to server when online
 *   S12-21         editOp patches queued entry locally; POSTs to server
 *   (full round-trip) complete offline → online sync cycle
 */

import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import 'fake-indexeddb/auto'
import { IDBFactory } from 'fake-indexeddb'
import { SyncManager } from './SyncManager.js'

// ── Helpers ───────────────────────────────────────────────────────────────────

function setOnline(online) {
    Object.defineProperty(navigator, 'onLine', { get: () => online, configurable: true })
}

function makePushResponse(overrides = {}) {
    const data = {
        applied_count:      1,
        conflict_count:     0,
        already_seen_count: 0,
        conflicts:          [],
        server_logical_seq: 5,
        authoritative_state: [],
        ...overrides,
    }
    return { ok: true, status: 200, json: vi.fn().mockResolvedValue(data) }
}

function makeGetResponse(data) {
    return { ok: true, status: 200, json: vi.fn().mockResolvedValue(data) }
}

function stdOp(overrides = {}) {
    return {
        storage_id:     1,
        product_id:     2,
        op_type:        'stock_in',
        quantity_delta: 50,
        unit:           'kg',
        ...overrides,
    }
}

// ── Per-test setup ────────────────────────────────────────────────────────────

let manager

beforeEach(async () => {
    // Fresh IndexedDB for each test (fake-indexeddb/auto installed globals once,
    // but each test needs an isolated database)
    global.indexedDB = new IDBFactory()

    // Start offline
    setOnline(false)

    // Meta tags expected by SyncManager
    document.head.innerHTML = `
        <meta name="csrf-token" content="test-csrf-token">
        <meta name="user-id"    content="42">
    `

    global.fetch = vi.fn()

    manager = new SyncManager()
    await manager.init()
})

afterEach(() => {
    manager.destroy()   // remove online / SW-sync listeners so they don't bleed into the next test
    vi.restoreAllMocks()
})

// ── Tests ─────────────────────────────────────────────────────────────────────

describe('SyncManager – offline flow end to end (S12-24)', () => {

    // ── S12-03 / S12-11 ──────────────────────────────────────────────────────

    it('queues op in pending_ops when offline and does not call fetch', async () => {
        const opId = await manager.recordOp(stdOp())

        expect(opId).toBeTypeOf('string')
        expect(opId.length).toBeGreaterThan(0)

        const pending = await manager.getPendingOps()
        expect(pending).toHaveLength(1)
        expect(pending[0].op_id).toBe(opId)
        expect(pending[0].quantity_delta).toBe(50)
        expect(pending[0].user_id).toBe(42)

        expect(fetch).not.toHaveBeenCalled()
    })

    // ── S12-12 ────────────────────────────────────────────────────────────────

    it('updates inventory_stock optimistically after recordOp', async () => {
        await manager.recordOp(stdOp({ quantity_delta: 80 }))

        const cache = await manager.getLocalQty(1, 2)
        expect(cache?.quantity).toBe(80)
        expect(cache?.optimistic).toBe(true)
    })

    it('accumulates optimistic deltas across multiple ops', async () => {
        await manager.recordOp(stdOp({ quantity_delta: 100 }))
        await manager.recordOp(stdOp({ op_type: 'stock_out', quantity_delta: -30 }))

        const cache = await manager.getLocalQty(1, 2)
        expect(cache?.quantity).toBe(70)
        expect(cache?.optimistic).toBe(true)
    })

    // ── S12-09 ────────────────────────────────────────────────────────────────

    it('assigns monotonically increasing logical_seq (Lamport clock)', async () => {
        for (let i = 0; i < 4; i++) {
            await manager.recordOp(stdOp())
        }

        const pending = await manager.getPendingOps()
        const seqs    = pending.map(op => op.logical_seq)

        expect(seqs).toEqual([...seqs].sort((a, b) => a - b))
        expect(new Set(seqs).size).toBe(4)  // all unique
    })

    // ── S12-13: no-op when offline ────────────────────────────────────────────

    it('sync() no-ops and leaves queue intact when offline', async () => {
        await manager.recordOp(stdOp())

        // still offline — sync() should bail immediately
        await manager.sync()

        expect(fetch).not.toHaveBeenCalled()
        expect(await manager.getPendingOps()).toHaveLength(1)
    })

    it('sync() no-ops when queue is empty', async () => {
        setOnline(true)
        await manager.sync()
        expect(fetch).not.toHaveBeenCalled()
    })

    // ── S12-22: online event wires to sync ────────────────────────────────────

    it('sync() is triggered when the online event fires', async () => {
        await manager.recordOp(stdOp())

        setOnline(true)
        // Use mockResolvedValue (not Once) so it handles any number of calls
        fetch.mockResolvedValue(makePushResponse({
            authoritative_state: [{ storage_id: 1, product_id: 2, quantity: 50, unit: 'kg' }],
        }))

        window.dispatchEvent(new Event('online'))

        // Wait for the queue to drain — confirms sync() ran to completion
        await vi.waitFor(() =>
            manager.getPendingOps().then(ops => expect(ops).toHaveLength(0)),
            { timeout: 2000 }
        )
        expect(fetch).toHaveBeenCalled()
    })

    // ── S12-14 / S12-15 / S12-16 / S12-17 ────────────────────────────────────

    it('sync() POSTs pending ops in logical_seq order to /api/sync/push', async () => {
        await manager.recordOp(stdOp({ quantity_delta: 100 }))
        await manager.recordOp(stdOp({ op_type: 'stock_out', quantity_delta: -25 }))

        setOnline(true)
        fetch.mockResolvedValueOnce(makePushResponse({
            applied_count:      2,
            server_logical_seq: 10,
            authoritative_state: [{ storage_id: 1, product_id: 2, quantity: 75, unit: 'kg' }],
        }))

        await manager.sync()

        // Correct endpoint and method
        expect(fetch).toHaveBeenCalledWith('/api/sync/push', expect.objectContaining({ method: 'POST' }))

        // Ops sent in causal order
        const body = JSON.parse(fetch.mock.calls[0][1].body)
        expect(body.ops).toHaveLength(2)
        expect(body.ops[0].logical_seq).toBeLessThan(body.ops[1].logical_seq)
    })

    it('sync() clears queue and applies authoritative state on success', async () => {
        await manager.recordOp(stdOp({ quantity_delta: 100 }))
        await manager.recordOp(stdOp({ op_type: 'stock_out', quantity_delta: -25 }))

        setOnline(true)
        fetch.mockResolvedValueOnce(makePushResponse({
            applied_count:      2,
            server_logical_seq: 10,
            authoritative_state: [{ storage_id: 1, product_id: 2, quantity: 75, unit: 'kg' }],
        }))

        await manager.sync()

        expect(await manager.getPendingOps()).toHaveLength(0)

        const cache = await manager.getLocalQty(1, 2)
        expect(cache?.quantity).toBe(75)
        expect(cache?.optimistic).toBe(false)
    })

    // ── S12-17: sync:completed event ─────────────────────────────────────────

    it('dispatches sync:completed with correct detail after successful sync', async () => {
        await manager.recordOp(stdOp())

        setOnline(true)
        fetch.mockResolvedValueOnce(makePushResponse({ applied_count: 1, conflict_count: 0 }))

        const completed = new Promise(resolve =>
            window.addEventListener('sync:completed', e => resolve(e.detail), { once: true })
        )

        await manager.sync()

        const detail = await completed
        expect(detail.applied).toBe(1)
        expect(detail.conflicts).toBe(0)
    })

    it('dispatches sync:conflicts when server returns conflicts', async () => {
        await manager.recordOp(stdOp())

        setOnline(true)
        fetch.mockResolvedValueOnce(makePushResponse({
            conflict_count: 1,
            conflicts: [{ op_id: 'some-id', reason: 'quantity_mismatch' }],
        }))

        const conflictsEvent = new Promise(resolve =>
            window.addEventListener('sync:conflicts', e => resolve(e.detail), { once: true })
        )

        await manager.sync()

        const detail = await conflictsEvent
        expect(detail.conflicts).toHaveLength(1)
    })

    // ── S12-18: failure leaves queue intact ───────────────────────────────────

    it('sync() leaves queue intact on network failure', async () => {
        await manager.recordOp(stdOp())

        setOnline(true)
        fetch.mockRejectedValueOnce(new Error('Network error'))

        await manager.sync()

        expect(await manager.getPendingOps()).toHaveLength(1)
    })

    it('sync() leaves queue intact on non-ok HTTP response', async () => {
        await manager.recordOp(stdOp())

        setOnline(true)
        fetch.mockResolvedValueOnce({
            ok:     false,
            status: 500,
            json:   vi.fn().mockResolvedValue({ message: 'Server Error' }),
        })

        await manager.sync()

        expect(await manager.getPendingOps()).toHaveLength(1)
    })

    // ── S12-19: pull ──────────────────────────────────────────────────────────

    it('pull() GETs /api/sync/pull and applies remote ops to local stock cache', async () => {
        setOnline(true)
        fetch.mockResolvedValueOnce(makeGetResponse({
            server_logical_seq: 10,
            remote_ops: [{
                storage_id:     1,
                product_id:     2,
                op_type:        'stock_in',
                quantity_delta: 200,
                unit:           'kg',
            }],
            pending_conflicts: [],
        }))

        await manager.pull()

        expect(fetch).toHaveBeenCalledWith(
            expect.stringContaining('/api/sync/pull'),
            expect.objectContaining({ method: 'GET' })
        )

        const cache = await manager.getLocalQty(1, 2)
        expect(cache?.quantity).toBe(200)
        expect(cache?.optimistic).toBe(false)
    })

    it('pull() advances Lamport clock so the next op has a higher logical_seq', async () => {
        setOnline(true)
        fetch.mockResolvedValueOnce(makeGetResponse({
            server_logical_seq: 99,
            remote_ops:         [],
            pending_conflicts:  [],
        }))

        await manager.pull()

        // Stay offline so recordOp does not trigger an auto-sync
        setOnline(false)
        await manager.recordOp(stdOp())

        const pending = await manager.getPendingOps()
        expect(pending[0].logical_seq).toBeGreaterThan(99)
    })

    it('pull() no-ops when offline', async () => {
        await manager.pull()
        expect(fetch).not.toHaveBeenCalled()
    })

    // ── S12-20: cancelOp ──────────────────────────────────────────────────────

    it('cancelOp removes op from local queue', async () => {
        const opId = await manager.recordOp(stdOp())

        await manager.cancelOp(opId, 'changed my mind')

        expect(await manager.getPendingOps()).toHaveLength(0)
    })

    it('cancelOp POSTs to /api/sync/cancel when online', async () => {
        const opId = await manager.recordOp(stdOp())

        setOnline(true)
        fetch.mockResolvedValueOnce({ ok: true, status: 200, json: vi.fn().mockResolvedValue({}) })

        await manager.cancelOp(opId, 'test reason')

        expect(fetch).toHaveBeenCalledWith(
            '/api/sync/cancel',
            expect.objectContaining({ method: 'POST' })
        )
        const body = JSON.parse(fetch.mock.calls[0][1].body)
        expect(body.op_id).toBe(opId)
        expect(body.reason).toBe('test reason')
    })

    it('cancelOp skips server call when offline', async () => {
        const opId = await manager.recordOp(stdOp())

        // still offline
        await manager.cancelOp(opId)

        expect(fetch).not.toHaveBeenCalled()
        expect(await manager.getPendingOps()).toHaveLength(0)
    })

    // ── S12-21: editOp ────────────────────────────────────────────────────────

    it('editOp patches quantity_delta and notes in the queued entry', async () => {
        const opId = await manager.recordOp(stdOp({ quantity_delta: 50 }))

        await manager.editOp(opId, { quantity_delta: 75, notes: 'adjusted' })

        const pending = await manager.getPendingOps()
        expect(pending[0].quantity_delta).toBe(75)
        expect(pending[0].notes).toBe('adjusted')
    })

    it('editOp POSTs to /api/sync/edit when online', async () => {
        const opId = await manager.recordOp(stdOp())

        setOnline(true)
        fetch.mockResolvedValueOnce({ ok: true, status: 200, json: vi.fn().mockResolvedValue({}) })

        await manager.editOp(opId, { quantity_delta: 99 })

        expect(fetch).toHaveBeenCalledWith(
            '/api/sync/edit',
            expect.objectContaining({ method: 'POST' })
        )
        const body = JSON.parse(fetch.mock.calls[0][1].body)
        expect(body.op_id).toBe(opId)
        expect(body.changes.quantity_delta).toBe(99)
    })

    // ── Concurrent sync guard ─────────────────────────────────────────────────

    it('guards against concurrent sync() calls (isSyncing flag)', async () => {
        await manager.recordOp(stdOp())

        setOnline(true)

        let resolveResponse
        const slowPromise = new Promise(r => { resolveResponse = r })
        fetch.mockReturnValueOnce(slowPromise)

        const s1 = manager.sync()
        const s2 = manager.sync()  // should no-op immediately

        resolveResponse(makePushResponse())
        await Promise.all([s1, s2])

        expect(fetch).toHaveBeenCalledOnce()
    })

    // ── Full offline → online round-trip (S12-24 core scenario) ──────────────

    it('full offline → online sync round-trip', async () => {
        // ── Phase 1: offline — queue two ops ─────────────────────────────────
        await manager.recordOp(stdOp({ op_type: 'stock_in',  quantity_delta: 100 }))
        await manager.recordOp(stdOp({ op_type: 'stock_out', quantity_delta: -25 }))

        let pending = await manager.getPendingOps()
        expect(pending).toHaveLength(2)

        // Optimistic quantity: 100 − 25 = 75
        let cache = await manager.getLocalQty(1, 2)
        expect(cache?.quantity).toBe(75)
        expect(cache?.optimistic).toBe(true)

        // ── Phase 2: online — server confirms with authoritative quantity ─────
        setOnline(true)
        fetch.mockResolvedValueOnce(makePushResponse({
            applied_count:      2,
            server_logical_seq: 2,
            authoritative_state: [{ storage_id: 1, product_id: 2, quantity: 75, unit: 'kg' }],
        }))

        const completedEvent = new Promise(resolve =>
            window.addEventListener('sync:completed', e => resolve(e.detail), { once: true })
        )

        await manager.sync()

        // ── Phase 3: verify post-sync state ───────────────────────────────────
        // Queue drained
        pending = await manager.getPendingOps()
        expect(pending).toHaveLength(0)

        // Authoritative stock applied, no longer optimistic
        cache = await manager.getLocalQty(1, 2)
        expect(cache?.quantity).toBe(75)
        expect(cache?.optimistic).toBe(false)

        // Event dispatched
        const detail = await completedEvent
        expect(detail.applied).toBe(2)
    })
})
