@props([
    'syncPending'   => 0,
    'syncConflicts' => 0,
    'syncApplied'   => 0,
    'syncOps'       => null,
])
@auth
@php
    $syncOps ??= collect([]);
    $gid       = auth()->user()->group_id ?? 0;
    $uid       = auth()->id();
    $isAdmin   = $gid === 1;
    $isSuperv  = in_array($gid, [2, 3, 4]);
    $isUser    = $gid > 4;
    $canAccept = in_array($gid, [1, 2, 3, 4]);
    $canMerge  = ($gid === 1);

    $locale = app()->getLocale();

    $opTypeLabel = [
        'stock_in'   => ['label' => __('locale.sync_op_stock_in'),   'color' => 'success'],
        'stock_out'  => ['label' => __('locale.sync_op_stock_out'),  'color' => 'danger'],
        'adjustment' => ['label' => __('locale.sync_op_adjustment'), 'color' => 'info'],
        'spoilage'   => ['label' => __('locale.sync_op_spoilage'),   'color' => 'warning'],
        'transfer'   => ['label' => __('locale.sync_op_transfer'),   'color' => 'secondary'],
    ];
    $statusBadge = [
        'pending'   => 'warning',
        'conflict'  => 'danger',
        'applied'   => 'success',
        'cancelled' => 'secondary',
    ];
    $statusLabel = [
        'pending'   => __('locale.sync_status_pending'),
        'conflict'  => __('locale.sync_status_conflict'),
        'applied'   => __('locale.sync_status_applied'),
        'cancelled' => __('locale.sync_status_cancelled'),
    ];
@endphp

<div class="card border-0 shadow-sm mb-4" id="sync-panel">

    {{-- ── Header ── --}}
    <div class="card-header bg-primary-subtle d-flex align-items-center justify-content-between flex-wrap gap-2 py-3">
        <div class="d-flex align-items-center gap-2">
            <span id="sync-status-dot"
                  style="width:10px;height:10px;border-radius:50%;display:inline-block;background:#6c757d;"
                  title="{{ __('locale.sync_checking') }}"></span>
            <h5 class="mb-0 fw-semibold">
                <i class="fas fa-sync-alt me-1 text-primary"></i> {{ __('locale.sync_title') }}
            </h5>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="badge bg-warning text-dark" title="{{ __('locale.sync_pending_tip') }}">
                <i class="fas fa-clock me-1"></i>
                <span id="sync-pending-count">{{ $syncPending ?? 0 }}</span> {{ __('locale.sync_pending') }}
            </span>
            <span class="badge bg-danger {{ ($syncConflicts ?? 0) > 0 ? '' : 'd-none' }}" id="sync-conflict-badge"
                  title="{{ __('locale.sync_conflicts_tip') }}">
                <i class="fas fa-exclamation-triangle me-1"></i>
                <span id="sync-conflict-count">{{ $syncConflicts ?? 0 }}</span> {{ __('locale.sync_conflicts') }}
            </span>
            <span class="badge bg-success" title="{{ __('locale.sync_applied_tip') }}">
                <i class="fas fa-check me-1"></i> {{ $syncApplied ?? 0 }} {{ __('locale.sync_applied_today') }}
            </span>
            <button class="btn btn-sm btn-primary" id="sync-now-btn" title="{{ __('locale.sync_now_tip') }}">
                <i class="fas fa-sync-alt me-1"></i> {{ __('locale.sync_now') }}
            </button>
            <a href="{{ route('inventory-ops.index') }}" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-list me-1"></i> {{ __('locale.sync_all_ops') }}
            </a>
            @if ($canAccept)
            <a href="{{ route('sync-audit-log.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-book me-1"></i> {{ __('locale.sync_log') }}
            </a>
            @endif
            @if ($isAdmin)
            <a href="{{ route('sync-sessions.index') }}" class="btn btn-sm btn-outline-info">
                <i class="fas fa-history me-1"></i> {{ __('locale.sync_sessions') }}
            </a>
            @endif
        </div>
    </div>

    {{-- ── Role label strip ── --}}
    <div class="px-3 py-2 bg-light border-bottom d-flex align-items-center gap-2 small">
        @if ($isAdmin)
            <span class="badge bg-danger">Admin</span>
            <span class="text-muted">{{ __('locale.sync_role_admin') }}</span>
        @elseif ($isSuperv)
            <span class="badge bg-warning text-dark">{{ __('locale.supervisor') ?? 'Supervisor' }}</span>
            <span class="text-muted">{{ __('locale.sync_role_supervisor') }}</span>
        @else
            <span class="badge bg-info">{{ __('locale.user') ?? 'User' }}</span>
            <span class="text-muted">{{ __('locale.sync_role_user') }}</span>
        @endif
    </div>

    {{-- ── Ops table ── --}}
    <div class="card-body p-0">
        @if (isset($syncOps) && $syncOps->isNotEmpty())
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small" id="sync-ops-table">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3" style="min-width:120px">{{ __('locale.sync_col_product') }}</th>
                        <th style="min-width:130px">{{ __('locale.sync_col_warehouse') }}</th>
                        @if (!$isUser)
                        <th style="min-width:110px">{{ __('locale.sync_col_user') }}</th>
                        @endif
                        <th>{{ __('locale.sync_col_type') }}</th>
                        <th>{{ __('locale.sync_col_qty') }}</th>
                        <th>{{ __('locale.sync_col_status') }}</th>
                        <th style="min-width:80px">{{ __('locale.sync_col_received') }}</th>
                        <th class="text-end pe-3" style="min-width:160px">{{ __('locale.sync_col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($syncOps as $op)
                    @php
                        $tinfo   = $opTypeLabel[$op->op_type] ?? ['label' => $op->op_type, 'color' => 'secondary'];
                        $isOwner = ($uid == $op->user_id);
                    @endphp
                    <tr id="sync-row-{{ $op->op_id }}"
                        class="{{ $op->sync_status === 'conflict' ? 'table-danger bg-danger-subtle' : '' }}">

                        <td class="ps-3 fw-semibold">{{ $op->product->name ?? '—' }}</td>
                        <td>{{ $op->storage->name ?? '—' }}</td>

                        @if (!$isUser)
                        <td>{{ $op->user->name ?? '—' }}</td>
                        @endif

                        <td>
                            <span class="badge bg-{{ $tinfo['color'] }} bg-opacity-25 text-{{ $tinfo['color'] }} border border-{{ $tinfo['color'] }}">
                                {{ $tinfo['label'] }}
                            </span>
                        </td>

                        <td class="fw-bold {{ $op->quantity_delta >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ $op->quantity_delta >= 0 ? '+' : '' }}{{ number_format((float)$op->quantity_delta, 1) }}
                            <span class="fw-normal text-muted">{{ $op->unit }}</span>
                        </td>

                        <td>
                            <span class="badge bg-{{ $statusBadge[$op->sync_status] ?? 'light' }}">
                                {{ $statusLabel[$op->sync_status] ?? $op->sync_status }}
                            </span>
                        </td>

                        <td class="text-muted text-nowrap">
                            {{ $op->server_received_at ? $op->server_received_at->format('d/m H:i') : '—' }}
                        </td>

                        <td class="text-end pe-3">
                            <div class="d-flex justify-content-end gap-1 flex-wrap">

                                @if ($canAccept && $op->sync_status === 'conflict')
                                <button class="btn btn-sm btn-success sync-action-btn"
                                        data-action="{{ route('inventory-ops.accept', $op->op_id) }}"
                                        data-confirm="{{ __('locale.sync_confirm_accept') }}"
                                        title="{{ __('locale.sync_btn_accept') }}">
                                    <i class="fas fa-check"></i>
                                </button>
                                @endif

                                @if ($op->sync_status === 'conflict')
                                <button class="btn btn-sm btn-outline-secondary sync-action-btn"
                                        data-action="{{ route('inventory-ops.discard', $op->op_id) }}"
                                        data-confirm="{{ __('locale.sync_confirm_discard') }}"
                                        title="{{ __('locale.sync_btn_discard') }}">
                                    <i class="fas fa-ban"></i>
                                </button>
                                @endif

                                @if ($op->sync_status === 'pending' && ($isOwner || $isAdmin))
                                <button class="btn btn-sm btn-outline-danger sync-action-btn"
                                        data-action="{{ route('inventory-ops.cancel', $op->op_id) }}"
                                        data-confirm="{{ __('locale.sync_confirm_cancel') }}"
                                        title="{{ __('locale.sync_btn_cancel') }}">
                                    <i class="fas fa-times"></i>
                                </button>
                                @endif

                                @if ($op->sync_status === 'pending' && ($isOwner || $isAdmin))
                                <button class="btn btn-sm btn-outline-warning sync-edit-btn"
                                        data-op-id="{{ $op->op_id }}"
                                        data-qty="{{ $op->quantity_delta }}"
                                        data-notes="{{ e($op->notes ?? '') }}"
                                        data-action="{{ route('inventory-ops.edit', $op->op_id) }}"
                                        title="{{ __('locale.sync_btn_edit') }}">
                                    <i class="fas fa-pencil-alt"></i>
                                </button>
                                @endif

                                @if ($canMerge && $op->sync_status === 'conflict' && $op->conflict_with_op_id)
                                <button class="btn btn-sm btn-outline-info sync-merge-btn"
                                        data-op-id="{{ $op->op_id }}"
                                        data-rival-id="{{ $op->conflict_with_op_id }}"
                                        data-qty="{{ $op->quantity_delta }}"
                                        title="{{ __('locale.sync_merge_tip') }}">
                                    <i class="fas fa-code-merge"></i>
                                </button>
                                @endif

                                @if ($isAdmin && $op->sync_status === 'conflict')
                                <button class="btn btn-sm btn-outline-dark sync-override-btn"
                                        data-action="{{ route('inventory-ops.override', $op->op_id) }}"
                                        title="{{ __('locale.sync_override_tip') }}">
                                    <i class="fas fa-sliders-h"></i>
                                </button>
                                @endif

                                @if ($canAccept)
                                <a href="{{ route('inventory-ops.history', $op->op_id) }}"
                                   class="btn btn-sm btn-label-info" title="{{ __('locale.sync_btn_history') }}" target="_blank">
                                    <i class="fas fa-history"></i>
                                </a>
                                @endif

                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="text-center py-5 text-muted">
            <i class="fas fa-check-circle fa-2x text-success mb-2 d-block"></i>
            <span class="small">{{ __('locale.sync_empty') }}</span>
        </div>
        @endif
    </div>

    @if (isset($syncOps) && $syncOps->isNotEmpty())
    <div class="card-footer bg-transparent py-2 d-flex justify-content-between align-items-center">
        <span class="text-muted small">
            {{ __('locale.sync_ops_shown', ['count' => $syncOps->count()]) }}
            @if ($isAdmin || $isSuperv)
             · {{ __('locale.sync_last_sync') }} <span id="sync-last-time">—</span>
            @endif
        </span>
        <a href="{{ route('inventory-ops.index') }}" class="text-primary small fw-semibold">
            {{ __('locale.sync_view_all') }} <i class="fas fa-arrow-right ms-1"></i>
        </a>
    </div>
    @endif
</div>

{{-- ── Edit Op Modal ── --}}
<div class="modal fade" id="sync-edit-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning-subtle">
                <h5 class="modal-title">
                    <i class="fas fa-pencil-alt me-1"></i> {{ __('locale.sync_edit_modal_title') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="sync-edit-form" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">{{ __('locale.sync_edit_qty_label') }}</label>
                        <input type="number" name="quantity_delta" id="edit-qty-input"
                               class="form-control" step="0.001" required
                               placeholder="{{ __('locale.sync_edit_qty_ph') }}">
                        <div class="form-text">{{ __('locale.sync_edit_qty_hint') }}</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">{{ __('locale.sync_edit_notes_label') }}</label>
                        <textarea name="notes" id="edit-notes-input" class="form-control" rows="2"
                                  placeholder="{{ __('locale.sync_edit_notes_ph') }}"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            {{ __('locale.sync_edit_reason_label') }} <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="reason" class="form-control" required
                               placeholder="{{ __('locale.sync_edit_reason_ph') }}">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('locale.sync_btn_close') }}</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save me-1"></i> {{ __('locale.sync_btn_save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ── Merge Ops Modal (admin only) ── --}}
@if ($canMerge)
<div class="modal fade" id="sync-merge-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-info-subtle">
                <h5 class="modal-title">
                    <i class="fas fa-code-merge me-1"></i> {{ __('locale.sync_merge_modal_title') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('inventory-ops.merge') }}" method="POST">
                @csrf
                <input type="hidden" name="op_a_id" id="merge-op-a">
                <input type="hidden" name="op_b_id" id="merge-op-b">
                <div class="modal-body">
                    <div class="alert alert-info py-2 small">
                        <i class="fas fa-info-circle me-1"></i>
                        {{ __('locale.sync_merge_info') }}
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">{{ __('locale.sync_merge_qty_label') }}</label>
                        <input type="number" name="merged_quantity" id="merge-qty-input"
                               class="form-control" step="0.001" required>
                        <div class="form-text">
                            {{ __('locale.sync_merge_op_a') }} <strong><span id="merge-qty-a">—</span></strong>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            {{ __('locale.sync_merge_reason_label') }} <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="reason" class="form-control" required
                               placeholder="{{ __('locale.sync_merge_reason_ph') }}">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('locale.sync_btn_close') }}</button>
                    <button type="submit" class="btn btn-info text-white">
                        <i class="fas fa-code-merge me-1"></i> {{ __('locale.sync_btn_merge') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- ── Override Modal (admin only) ── --}}
@if ($isAdmin)
<div class="modal fade" id="sync-override-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title">
                    <i class="fas fa-sliders-h me-1"></i> {{ __('locale.sync_override_title') }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="sync-override-form" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-danger py-2 small">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        {{ __('locale.sync_override_warning') }}
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            {{ __('locale.sync_override_qty_label') }} <span class="text-danger">*</span>
                        </label>
                        <input type="number" name="quantity" id="override-qty-input"
                               class="form-control" min="0" step="0.001" required
                               placeholder="{{ __('locale.sync_override_qty_ph') }}">
                        <div class="form-text">{{ __('locale.sync_override_qty_hint') }}</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('locale.sync_btn_close') }}</button>
                    <button type="submit" class="btn btn-dark">
                        <i class="fas fa-check me-1"></i> {{ __('locale.sync_btn_apply') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<script>
(function () {
    const locale = '{{ $locale }}';

    function updateDot() {
        const dot = document.getElementById('sync-status-dot');
        if (!dot) return;
        const online = navigator.onLine;
        dot.style.background = online ? '#198754' : '#dc3545';
        dot.title = online ? '{{ __('locale.sync_online') }}' : '{{ __('locale.sync_offline') }}';
    }
    updateDot();
    window.addEventListener('online',  updateDot);
    window.addEventListener('offline', updateDot);

    const syncBtn = document.getElementById('sync-now-btn');
    if (syncBtn) {
        syncBtn.addEventListener('click', function () {
            if (window.syncManager) {
                syncBtn.disabled = true;
                syncBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> {{ __('locale.sync_now') }}…';
                window.syncManager.sync().finally(function () {
                    syncBtn.disabled = false;
                    syncBtn.innerHTML = '<i class="fas fa-sync-alt me-1"></i> {{ __('locale.sync_now') }}';
                });
            } else {
                window.location.reload();
            }
        });
    }

    (function setLastSync() {
        const el = document.getElementById('sync-last-time');
        if (!el) return;
        const raw = localStorage.getItem('agristock_last_sync');
        if (raw) {
            const d = new Date(raw);
            el.textContent = d.toLocaleTimeString(locale === 'fr' ? 'fr-FR' : 'en-GB', { hour: '2-digit', minute: '2-digit' });
        }
    })();

    document.querySelectorAll('#sync-ops-table .sync-action-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const msg = btn.getAttribute('data-confirm');
            if (msg && !confirm(msg)) return;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = btn.getAttribute('data-action');
            form.innerHTML = '<input type="hidden" name="_token" value="{{ csrf_token() }}">';
            document.body.appendChild(form);
            form.submit();
        });
    });

    document.querySelectorAll('#sync-ops-table .sync-edit-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('edit-qty-input').value   = btn.getAttribute('data-qty') || '';
            document.getElementById('edit-notes-input').value = btn.getAttribute('data-notes') || '';
            document.getElementById('sync-edit-form').action  = btn.getAttribute('data-action');
            bootstrap.Modal.getOrCreateInstance(document.getElementById('sync-edit-modal')).show();
        });
    });

    document.querySelectorAll('#sync-ops-table .sync-merge-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const mergeModal = document.getElementById('sync-merge-modal');
            if (!mergeModal) return;
            document.getElementById('merge-op-a').value        = btn.getAttribute('data-op-id');
            document.getElementById('merge-op-b').value        = btn.getAttribute('data-rival-id');
            const qty = btn.getAttribute('data-qty') || '0';
            document.getElementById('merge-qty-a').textContent = qty;
            document.getElementById('merge-qty-input').value   = qty;
            bootstrap.Modal.getOrCreateInstance(mergeModal).show();
        });
    });

    document.querySelectorAll('#sync-ops-table .sync-override-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const overModal = document.getElementById('sync-override-modal');
            if (!overModal) return;
            document.getElementById('sync-override-form').action = btn.getAttribute('data-action');
            bootstrap.Modal.getOrCreateInstance(overModal).show();
        });
    });

    window.addEventListener('sync:conflicts', function (e) {
        const count   = (e.detail?.conflicts || []).length;
        const countEl = document.getElementById('sync-conflict-count');
        const badge   = document.getElementById('sync-conflict-badge');
        if (countEl) countEl.textContent = count;
        if (badge) badge.classList.toggle('d-none', count === 0);
    });
})();
</script>
@endauth
