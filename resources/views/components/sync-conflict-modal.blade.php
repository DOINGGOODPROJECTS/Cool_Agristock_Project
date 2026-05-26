{{-- Conflict resolution modal — fires on window 'sync:conflicts' event.
     Included once in layouts/app.blade.php; visible on every authenticated page. --}}
@auth
@php
    $gid       = auth()->user()->group_id ?? 0;
    $canAccept = in_array($gid, [1, 2, 3, 4]);
    $canMerge  = ($gid === 1);
@endphp

<div class="modal fade" id="sync-conflict-modal" tabindex="-1" aria-modal="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            {{-- Header --}}
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    {{ __('locale.sync_conflict_modal_title') }}
                    <span id="scm-counter" class="badge bg-white text-danger ms-2" style="font-size:.7rem"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            {{-- Body --}}
            <div class="modal-body">

                {{-- Produit / Entrepôt --}}
                <div class="row mb-3">
                    <div class="col-sm-6 mb-2">
                        <div class="border rounded px-3 py-2 h-100">
                            <div class="text-muted small">{{ __('locale.sync_col_product') }}</div>
                            <div class="fw-semibold" id="scm-product">—</div>
                        </div>
                    </div>
                    <div class="col-sm-6 mb-2">
                        <div class="border rounded px-3 py-2 h-100">
                            <div class="text-muted small">{{ __('locale.sync_col_warehouse') }}</div>
                            <div class="fw-semibold" id="scm-storage">—</div>
                        </div>
                    </div>
                </div>

                {{-- Quantités comparées --}}
                <div class="row mb-3">
                    <div class="col-6">
                        <div class="card bg-warning-subtle border-warning-subtle">
                            <div class="card-body py-2">
                                <div class="text-muted small mb-1">{{ __('locale.scm_device_recorded') }}</div>
                                <div class="fw-bold fs-5" id="scm-device-qty">—</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card bg-info-subtle border-info-subtle">
                            <div class="card-body py-2">
                                <div class="text-muted small mb-1">{{ __('locale.scm_server_has') }}</div>
                                <div class="fw-bold fs-5" id="scm-server-qty">—</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Raison --}}
                <div class="alert alert-danger py-2 small mb-3">
                    <i class="fas fa-info-circle me-1"></i>
                    <strong>{{ __('locale.scm_conflict_reason') }}</strong>
                    <span id="scm-reason">—</span>
                </div>

                {{-- Sous-formulaire Fusionner (admin only) --}}
                @if ($canMerge)
                <div id="scm-merge-form" style="display:none">
                    <div class="card border-warning mb-3">
                        <div class="card-header bg-warning-subtle py-2 fw-semibold small">
                            <i class="fas fa-code-branch me-1"></i> {{ __('locale.scm_merge_header') }}
                        </div>
                        <div class="card-body">
                            <div class="row mb-2">
                                <div class="col-6">
                                    <label class="form-label small mb-1">{{ __('locale.scm_qty_a') }}</label>
                                    <input type="number" step="0.001" class="form-control form-control-sm"
                                           id="scm-merge-qty-a" readonly>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small mb-1">{{ __('locale.scm_qty_b') }}</label>
                                    <input type="number" step="0.001" class="form-control form-control-sm"
                                           id="scm-merge-qty-b" readonly>
                                </div>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small mb-1">{{ __('locale.scm_merged_qty') }} <span class="text-danger">*</span></label>
                                <input type="number" step="0.001" class="form-control form-control-sm"
                                       id="scm-merged-qty" placeholder="{{ __('locale.scm_merged_qty_ph') }}">
                            </div>
                            <div>
                                <label class="form-label small mb-1">{{ __('locale.scm_merge_reason') }} <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm"
                                       id="scm-merge-reason" placeholder="{{ __('locale.scm_merge_reason_ph') }}">
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Sous-formulaire Corriger (tous les groupes) --}}
                <div id="scm-edit-form" style="display:none">
                    <div class="card border-info mb-3">
                        <div class="card-header bg-info-subtle py-2 fw-semibold small">
                            <i class="fas fa-pencil-alt me-1"></i> {{ __('locale.scm_correct_header') }}
                        </div>
                        <div class="card-body">
                            <div class="mb-2">
                                <label class="form-label small mb-1">{{ __('locale.scm_new_qty_delta') }} <span class="text-danger">*</span></label>
                                <input type="number" step="0.001" class="form-control form-control-sm"
                                       id="scm-edit-qty" placeholder="{{ __('locale.scm_qty_ph') }}">
                            </div>
                            <div>
                                <label class="form-label small mb-1">{{ __('locale.sync_edit_notes_label') }}</label>
                                <textarea class="form-control form-control-sm" rows="2"
                                          id="scm-edit-notes" placeholder="{{ __('locale.sync_edit_notes_ph') }}"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Feedback inline --}}
                <div id="scm-feedback" class="alert py-2 small mb-0" style="display:none"></div>

            </div>{{-- /modal-body --}}

            {{-- Footer --}}
            <div class="modal-footer d-flex justify-content-between align-items-center flex-wrap gap-2">

                {{-- Navigation prev/next --}}
                <div id="scm-nav" class="d-flex gap-1" style="display:none !important">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="scm-prev">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="scm-next">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>

                {{-- Action buttons --}}
                <div class="d-flex gap-2 flex-wrap">
                    {{-- Accepter — groups 1-4 --}}
                    @if ($canAccept)
                    <button type="button" class="btn btn-success btn-sm" id="scm-accept-btn">
                        <i class="fas fa-check me-1"></i> {{ __('locale.sync_btn_accept') }}
                    </button>
                    @endif

                    {{-- Ignorer — all groups --}}
                    <button type="button" class="btn btn-secondary btn-sm" id="scm-discard-btn">
                        <i class="fas fa-ban me-1"></i> {{ __('locale.sync_btn_discard') }}
                    </button>

                    {{-- Fusionner toggle / confirm — admin only --}}
                    @if ($canMerge)
                    <button type="button" class="btn btn-warning btn-sm" id="scm-merge-toggle-btn">
                        <i class="fas fa-code-branch me-1"></i> {{ __('locale.sync_btn_merge') }}
                    </button>
                    <button type="button" class="btn btn-warning btn-sm d-none" id="scm-merge-confirm-btn">
                        <i class="fas fa-check me-1"></i> {{ __('locale.scm_confirm_merge') }}
                    </button>
                    @endif

                    {{-- Corriger toggle / confirm — all groups --}}
                    <button type="button" class="btn btn-info btn-sm" id="scm-edit-toggle-btn">
                        <i class="fas fa-pencil-alt me-1"></i> {{ __('locale.sync_btn_edit') }}
                    </button>
                    <button type="button" class="btn btn-info btn-sm d-none" id="scm-edit-confirm-btn">
                        <i class="fas fa-check me-1"></i> {{ __('locale.scm_confirm_correct') }}
                    </button>
                </div>

            </div>{{-- /modal-footer --}}
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    const SCM_I18N = {
        accepted:          @json(__('locale.scm_accepted')),
        discarded:         @json(__('locale.scm_discarded')),
        merged:            @json(__('locale.scm_merged')),
        corrected:         @json(__('locale.scm_corrected')),
        mergeRequired:     @json(__('locale.scm_merge_required')),
        noRival:           @json(__('locale.scm_no_rival')),
        qtyRequired:       @json(__('locale.scm_qty_required')),
        error:             @json(__('locale.scm_error')),
    };

    const $ = id => document.getElementById(id);

    const CSRF = () =>
        document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    let conflicts = [];
    let idx       = 0;
    let working   = false;
    let bsModal   = null;

    function getModal() {
        if (!bsModal) bsModal = new bootstrap.Modal($('sync-conflict-modal'), { backdrop: 'static' });
        return bsModal;
    }

    function current() { return conflicts[idx] ?? null; }

    // ── Render ────────────────────────────────────────────────────────────

    function render() {
        const c = current();
        if (!c) return;

        // Counter badge
        $('scm-counter').textContent = conflicts.length > 1
            ? `${idx + 1} / ${conflicts.length}`
            : '';

        // Info
        $('scm-product').textContent = c.product?.name ?? `#${c.product_id}`;
        $('scm-storage').textContent = c.storage?.name ?? `#${c.storage_id}`;
        $('scm-reason').textContent  = c.conflict_reason ?? '—';

        const delta = parseFloat(c.quantity_delta) || 0;
        $('scm-device-qty').textContent = (delta >= 0 ? '+' : '') + delta + ' ' + (c.unit ?? '');
        $('scm-server-qty').textContent = (c.server_qty ?? '?') + ' ' + (c.unit ?? '');

        // Navigation
        const nav = $('scm-nav');
        if (nav) {
            nav.style.setProperty('display', conflicts.length > 1 ? 'flex' : 'none', 'important');
        }
        if ($('scm-prev')) $('scm-prev').disabled = idx === 0;
        if ($('scm-next')) $('scm-next').disabled = idx === conflicts.length - 1;

        // Merge sub-form defaults
        if ($('scm-merge-qty-a')) $('scm-merge-qty-a').value = delta;
        if ($('scm-merge-qty-b')) $('scm-merge-qty-b').value = c.rival_qty ?? '';
        if ($('scm-merged-qty'))  $('scm-merged-qty').value  = '';
        if ($('scm-merge-reason')) $('scm-merge-reason').value = '';

        // Edit sub-form default
        if ($('scm-edit-qty'))   $('scm-edit-qty').value   = delta;
        if ($('scm-edit-notes')) $('scm-edit-notes').value  = '';

        resetSubForms();
        clearFeedback();
    }

    function resetSubForms() {
        [$('scm-merge-form'), $('scm-edit-form')].forEach(el => { if (el) el.style.display = 'none'; });
        [$('scm-merge-confirm-btn'), $('scm-edit-confirm-btn')].forEach(el => el?.classList.add('d-none'));
        [$('scm-merge-toggle-btn'), $('scm-edit-toggle-btn')].forEach(el => el?.classList.remove('d-none'));
    }

    function clearFeedback() {
        const fb = $('scm-feedback');
        if (fb) { fb.style.display = 'none'; fb.textContent = ''; }
    }

    function showFeedback(msg, type = 'success') {
        const fb = $('scm-feedback');
        if (!fb) return;
        fb.className     = `alert alert-${type} py-2 small mb-0`;
        fb.textContent   = msg;
        fb.style.display = '';
    }

    // ── API helper ────────────────────────────────────────────────────────

    async function apiPost(path, body) {
        const res = await fetch(path, {
            method:      'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type':     'application/json',
                'Accept':           'application/json',
                'X-CSRF-TOKEN':     CSRF(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(body),
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) throw new Error(data.message ?? `HTTP ${res.status}`);
        return data;
    }

    // ── Action dispatcher ─────────────────────────────────────────────────

    async function handleAction(action) {
        if (working) return;
        const c = current();
        if (!c) return;

        working = true;
        clearFeedback();

        try {
            if (action === 'accept') {
                await apiPost('/api/sync/resolve-conflict', {
                    op_id: c.op_id, resolution: 'accept',
                });
                showFeedback(SCM_I18N.accepted, 'success');

            } else if (action === 'discard') {
                await apiPost('/api/sync/resolve-conflict', {
                    op_id: c.op_id, resolution: 'discard',
                });
                showFeedback(SCM_I18N.discarded, 'secondary');

            } else if (action === 'merge') {
                const qty    = parseFloat($('scm-merged-qty')?.value);
                const reason = $('scm-merge-reason')?.value?.trim() ?? '';
                if (isNaN(qty) || !reason) {
                    showFeedback(SCM_I18N.mergeRequired, 'warning');
                    working = false; return;
                }
                if (!c.conflict_with_op_id) {
                    showFeedback(SCM_I18N.noRival, 'warning');
                    working = false; return;
                }
                await apiPost('/api/sync/resolve-conflict', {
                    op_id:           c.op_id,
                    resolution:      'merge',
                    merge_op_id:     c.conflict_with_op_id,
                    merged_quantity: qty,
                    reason,
                });
                showFeedback(SCM_I18N.merged, 'success');

            } else if (action === 'correct') {
                const newQty = parseFloat($('scm-edit-qty')?.value);
                const notes  = $('scm-edit-notes')?.value?.trim() ?? '';
                if (isNaN(newQty)) {
                    showFeedback(SCM_I18N.qtyRequired, 'warning');
                    working = false; return;
                }
                // Discard the conflicted op then re-queue corrected op via SyncManager
                await apiPost('/api/sync/resolve-conflict', {
                    op_id: c.op_id, resolution: 'discard',
                });
                if (window.syncManager) {
                    await window.syncManager.recordOp({
                        storage_id:     c.storage_id,
                        product_id:     c.product_id,
                        stock_id:       c.stock_id ?? null,
                        op_type:        c.op_type ?? 'correction',
                        quantity_delta: newQty,
                        unit:           c.unit ?? 'kg',
                        notes,
                    });
                }
                showFeedback(SCM_I18N.corrected, 'info');
            }

            // Remove resolved conflict from the local queue
            conflicts.splice(idx, 1);
            if (idx >= conflicts.length) idx = Math.max(0, conflicts.length - 1);

            // Refresh local IDB stock cache after accept / merge
            if (window.syncManager && (action === 'accept' || action === 'merge')) {
                window.syncManager.pull().catch(() => {});
            }

            if (conflicts.length === 0) {
                setTimeout(() => getModal().hide(), 1400);
            } else {
                setTimeout(render, 300);
            }

        } catch (err) {
            showFeedback(SCM_I18N.error + ' ' + err.message, 'danger');
        } finally {
            working = false;
        }
    }

    // ── Boot — wire events after DOM ready ────────────────────────────────

    document.addEventListener('DOMContentLoaded', function () {
        if (!$('sync-conflict-modal')) return;

        // Navigation
        $('scm-prev')?.addEventListener('click', () => { if (idx > 0) { idx--; render(); } });
        $('scm-next')?.addEventListener('click', () => { if (idx < conflicts.length - 1) { idx++; render(); } });

        // Action buttons
        $('scm-accept-btn')?.addEventListener('click',  () => handleAction('accept'));
        $('scm-discard-btn')?.addEventListener('click', () => handleAction('discard'));

        // Fusionner toggle / confirm
        $('scm-merge-toggle-btn')?.addEventListener('click', () => {
            $('scm-merge-form').style.display = '';
            $('scm-merge-toggle-btn')?.classList.add('d-none');
            $('scm-merge-confirm-btn')?.classList.remove('d-none');
            $('scm-edit-form').style.display = 'none';
            $('scm-edit-toggle-btn')?.classList.remove('d-none');
            $('scm-edit-confirm-btn')?.classList.add('d-none');
        });
        $('scm-merge-confirm-btn')?.addEventListener('click', () => handleAction('merge'));

        // Corriger toggle / confirm
        $('scm-edit-toggle-btn')?.addEventListener('click', () => {
            const c = current();
            $('scm-edit-form').style.display = '';
            if (c && $('scm-edit-qty')) $('scm-edit-qty').value = c.quantity_delta ?? '';
            $('scm-edit-toggle-btn')?.classList.add('d-none');
            $('scm-edit-confirm-btn')?.classList.remove('d-none');
            $('scm-merge-form').style.display = 'none';
            $('scm-merge-toggle-btn')?.classList.remove('d-none');
            $('scm-merge-confirm-btn')?.classList.add('d-none');
        });
        $('scm-edit-confirm-btn')?.addEventListener('click', () => handleAction('correct'));

        // Listen for sync:conflicts dispatched by SyncManager
        window.addEventListener('sync:conflicts', function (e) {
            const incoming = Array.isArray(e.detail?.conflicts) ? e.detail.conflicts : [];
            if (!incoming.length) return;
            conflicts = incoming;
            idx = 0;
            render();
            getModal().show();
        });
    });
})();
</script>
@endauth
