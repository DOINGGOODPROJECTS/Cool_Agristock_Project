<x-app-layout>
    @push('links')
    <link href="{{ asset('libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('libs/datatables.net-buttons-bs5/css/buttons.bootstrap5.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('libs/datatables.net-responsive-bs5/css/responsive.bootstrap5.min.css') }}" rel="stylesheet" type="text/css" />
    @endpush

    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Inventory Operations</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('locale.dashboard')</a></li>
                        <li class="breadcrumb-item active">Inventory Ops</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <table id="datatable-buttons" class="table table-hover table-bordered table-striped dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                        <thead>
                            @if (isGroupAuthorized([1]))
                            <th class="no-sort" style="width:32px"></th>
                            @endif
                            <th>#</th>
                            <th>Op ID</th>
                            <th>User</th>
                            <th>Device</th>
                            <th>Type</th>
                            <th>Product</th>
                            <th>Storage</th>
                            <th>Qty Delta</th>
                            <th>Status</th>
                            <th>Received At</th>
                            <th>@lang('locale.actions')</th>
                        </thead>
                        <tbody>
                            @foreach ($ops as $op)
                            <tr>
                                @if (isGroupAuthorized([1]))
                                <td class="text-center">
                                    <input type="checkbox" class="op-select form-check-input"
                                           value="{{ $op->op_id }}"
                                           {{ !in_array($op->sync_status, ['conflict', 'pending']) ? 'disabled' : '' }}>
                                </td>
                                @endif
                                <td>{{ $loop->iteration }}</td>
                                <td><span class="font-monospace small" title="{{ $op->op_id }}">{{ substr($op->op_id, 0, 8) }}…</span></td>
                                <td>{{ $op->user->name ?? '—' }}</td>
                                <td>{{ $op->device_id }}</td>
                                <td><span class="badge bg-secondary">{{ str_replace('_', ' ', $op->op_type) }}</span></td>
                                <td>{{ $op->product->name ?? '—' }}</td>
                                <td>{{ $op->storage->name ?? '—' }}</td>
                                <td>{{ $op->quantity_delta > 0 ? '+' : '' }}{{ $op->quantity_delta }} {{ $op->unit }}</td>
                                <td>
                                    @php
                                        $badge = match($op->sync_status) {
                                            'applied'    => 'success',
                                            'pending'    => 'warning',
                                            'conflict'   => 'danger',
                                            'cancelled'  => 'secondary',
                                            'superseded' => 'dark',
                                            default      => 'light',
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $badge }}">{{ $op->sync_status }}</span>
                                    @if ($op->sync_status === 'conflict' && $op->conflict_reason)
                                    <i class="fas fa-info-circle text-danger ms-1"
                                       data-bs-toggle="tooltip"
                                       title="{{ $op->conflict_reason }}{{ $op->conflict_with_op_id ? ' (conflicts with '.substr($op->conflict_with_op_id,0,8).'…)' : '' }}">
                                    </i>
                                    @endif
                                </td>
                                <td>{{ $op->server_received_at ? date('d/m/Y H:i', strtotime($op->server_received_at)) : '—' }}</td>
                                <td>
                                    {{-- Accept + Discard: admin/supervisor, conflict/pending --}}
                                    @if (isGroupAuthorized([1,2,3,4]) && in_array($op->sync_status, ['conflict','pending']))
                                    <form action="{{ route('inventory-ops.accept', $op->op_id) }}" method="POST" style="display:inline-block">
                                        @csrf
                                        <button class="btn btn-sm btn-label-success" title="Accept" onclick="return confirm('Accept this op?')"><i class="fas fa-check"></i></button>
                                    </form>
                                    <form action="{{ route('inventory-ops.discard', $op->op_id) }}" method="POST" style="display:inline-block">
                                        @csrf
                                        <button class="btn btn-sm btn-label-warning" title="Discard" onclick="return confirm('Discard this op?')"><i class="fas fa-ban"></i></button>
                                    </form>
                                    @endif

                                    {{-- Discard: lower tiers on own conflict ops --}}
                                    @if (!isGroupAuthorized([1,2,3,4]) && $op->sync_status === 'conflict')
                                    <form action="{{ route('inventory-ops.discard', $op->op_id) }}" method="POST" style="display:inline-block">
                                        @csrf
                                        <button class="btn btn-sm btn-label-warning" title="Discard" onclick="return confirm('Discard this op?')"><i class="fas fa-ban"></i></button>
                                    </form>
                                    @endif

                                    {{-- Cancel: owner or admin, pending only --}}
                                    @if ($op->sync_status === 'pending' && (auth()->id() === $op->user_id || isGroupAuthorized([1])))
                                    <form action="{{ route('inventory-ops.cancel', $op->op_id) }}" method="POST" style="display:inline-block">
                                        @csrf
                                        <button class="btn btn-sm btn-label-danger" title="Cancel" onclick="return confirm('Cancel this op?')"><i class="fas fa-times"></i></button>
                                    </form>
                                    @endif

                                    {{-- Edit: owner or admin, pending only --}}
                                    @if ($op->sync_status === 'pending' && (auth()->id() === $op->user_id || isGroupAuthorized([1])))
                                    <button class="btn btn-sm btn-label-info" title="Edit"
                                        data-bs-toggle="modal" data-bs-target="#edit-op-modal"
                                        data-op-id="{{ $op->op_id }}"
                                        data-qty="{{ $op->quantity_delta }}"
                                        data-notes="{{ $op->notes }}">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    @endif

                                    {{-- Override: admin/supervisor, conflict/pending --}}
                                    @if (isGroupAuthorized([1,2,3,4]) && in_array($op->sync_status, ['conflict','pending']))
                                    <button class="btn btn-sm btn-label-dark" title="Override Stock"
                                        data-bs-toggle="modal" data-bs-target="#override-modal"
                                        data-op-id="{{ $op->op_id }}"
                                        data-unit="{{ $op->unit }}">
                                        <i class="fas fa-balance-scale"></i>
                                    </button>
                                    @endif

                                    {{-- Audit trail --}}
                                    <button class="btn btn-sm btn-outline-secondary btn-history" title="Audit Trail"
                                        data-op-id="{{ $op->op_id }}"
                                        data-history-url="{{ route('inventory-ops.history', $op->op_id) }}">
                                        <i class="fas fa-history"></i>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Merge floating action bar (admin only) --}}
    @if (isGroupAuthorized([1]))
    <div id="merge-bar" class="position-fixed bottom-0 start-50 translate-middle-x mb-3
         bg-dark text-white rounded-pill px-4 py-2 shadow-lg d-flex align-items-center gap-3"
         style="display:none !important; z-index:1050">
        <span id="merge-bar-label" class="small fw-semibold"></span>
        <button type="button" class="btn btn-sm btn-warning" id="open-merge-btn" disabled>
            <i class="fas fa-code-branch me-1"></i> Merge
        </button>
        <button type="button" class="btn btn-sm btn-outline-light btn-close-white" id="clear-selection-btn" title="Clear selection">
            <i class="fas fa-times"></i>
        </button>
    </div>
    @endif

    {{-- Edit Op Modal --}}
    <div class="modal fade" id="edit-op-modal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" id="edit-op-form">
                @csrf
                @method('PUT')
                <div class="modal-content">
                    <div class="modal-header bg-primary-subtle">
                        <h5 class="modal-title">Edit Operation</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Quantity Delta</label>
                            <input type="number" step="0.001" class="form-control" name="quantity_delta" id="edit-qty" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" id="edit-notes" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Reason <span class="text-muted small">(recorded in audit log)</span></label>
                            <input type="text" class="form-control" name="reason" id="edit-reason"
                                   placeholder="e.g. Typo correction, physical recount…">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Save <i class="fas fa-check"></i></button>
                        <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Merge Modal (admin only) --}}
    @if (isGroupAuthorized([1]))
    <div class="modal fade" id="merge-modal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('inventory-ops.merge') }}">
                @csrf
                <input type="hidden" name="op_a_id" id="merge-op-a">
                <input type="hidden" name="op_b_id" id="merge-op-b">
                <div class="modal-content">
                    <div class="modal-header bg-warning-subtle">
                        <h5 class="modal-title"><i class="fas fa-code-branch me-1"></i> Merge Operations</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info small mb-3 py-2">
                            <strong>Op A:</strong> <span id="merge-op-a-label" class="font-monospace"></span><br>
                            <strong>Op B:</strong> <span id="merge-op-b-label" class="font-monospace"></span>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Merged Quantity Delta <span class="text-danger">*</span></label>
                            <input type="number" step="0.001" class="form-control" name="merged_quantity" id="merge-qty" required>
                            <div class="form-text">The reconciled quantity delta to apply to stock.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Reason <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="reason" id="merge-reason" required
                                   placeholder="e.g. Physical recount reconciliation…">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-warning">Merge &amp; Apply <i class="fas fa-check ms-1"></i></button>
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- Override Modal (admin/supervisor) --}}
    @if (isGroupAuthorized([1,2,3,4]))
    <div class="modal fade" id="override-modal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" id="override-form">
                @csrf
                <div class="modal-content">
                    <div class="modal-header bg-dark text-white">
                        <h5 class="modal-title"><i class="fas fa-balance-scale me-1"></i> Override Stock Quantity</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning small mb-3 py-2">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            This force-sets the stock quantity to the exact value entered, bypassing normal delta logic.
                        </div>
                        <div class="mb-3">
                            <label class="form-label">
                                Exact Quantity
                                <span id="override-unit" class="text-muted small ms-1"></span>
                            </label>
                            <input type="number" step="0.001" min="0" class="form-control"
                                   name="quantity" id="override-qty" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-dark">Apply Override</button>
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- Audit Trail Modal --}}
    <div class="modal fade" id="history-modal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-secondary-subtle">
                    <h5 class="modal-title"><i class="fas fa-history me-1"></i> Audit Trail — <span id="history-op-id" class="font-monospace small"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <div id="history-loading" class="text-center py-4">
                        <div class="spinner-border text-secondary" role="status"></div>
                    </div>
                    <div id="history-empty" class="text-center text-muted py-4" style="display:none">No audit entries found for this op.</div>
                    <table id="history-table" class="table table-sm table-bordered mb-0" style="display:none">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Action</th>
                                <th>Actor</th>
                                <th>Group</th>
                                <th>Device</th>
                                <th>Reason</th>
                                <th>Before / After</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody id="history-body"></tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Before/After drill-down modal --}}
    <div class="modal fade" id="history-diff-modal" tabindex="-1" style="z-index:1060">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-info-subtle">
                    <h5 class="modal-title">Before / After</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-6">
                            <h6 class="text-muted">Before</h6>
                            <pre id="hdiff-before" class="bg-light p-2 rounded small" style="min-height:60px;white-space:pre-wrap"></pre>
                        </div>
                        <div class="col-6">
                            <h6 class="text-muted">After</h6>
                            <pre id="hdiff-after" class="bg-light p-2 rounded small" style="min-height:60px;white-space:pre-wrap"></pre>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="{{ asset('libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('libs/datatables.net-bs5/js/dataTables.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('libs/datatables.net-buttons/js/dataTables.buttons.min.js') }}"></script>
    <script src="{{ asset('libs/datatables.net-buttons-bs5/js/buttons.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('libs/jszip/jszip.min.js') }}"></script>
    <script src="{{ asset('libs/pdfmake/build/pdfmake.min.js') }}"></script>
    <script src="{{ asset('libs/pdfmake/build/vfs_fonts.js') }}"></script>
    <script src="{{ asset('libs/datatables.net-buttons/js/buttons.html5.min.js') }}"></script>
    <script src="{{ asset('libs/datatables.net-buttons/js/buttons.print.min.js') }}"></script>
    <script src="{{ asset('libs/datatables.net-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('libs/datatables.net-responsive-bs5/js/responsive.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('js/pages/datatables-extension.init.js') }}"></script>
    <script>
        // Bootstrap tooltips
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
            new bootstrap.Tooltip(el);
        });

        // ── Edit modal ────────────────────────────────────────────────────
        document.getElementById('edit-op-modal').addEventListener('show.bs.modal', function (e) {
            const btn = e.relatedTarget;
            document.getElementById('edit-qty').value    = btn.getAttribute('data-qty');
            document.getElementById('edit-notes').value  = btn.getAttribute('data-notes') || '';
            document.getElementById('edit-reason').value = '';
            document.getElementById('edit-op-form').action = '/inventory-ops/' + btn.getAttribute('data-op-id');
        });

        // ── Override modal ────────────────────────────────────────────────
        @if (isGroupAuthorized([1,2,3,4]))
        document.getElementById('override-modal').addEventListener('show.bs.modal', function (e) {
            const btn  = e.relatedTarget;
            const opId = btn.getAttribute('data-op-id');
            const unit = btn.getAttribute('data-unit');
            document.getElementById('override-form').action = '/inventory-ops/' + opId + '/override';
            document.getElementById('override-unit').textContent = unit ? '(' + unit + ')' : '';
            document.getElementById('override-qty').value = '';
        });
        @endif

        // ── Merge selection bar ───────────────────────────────────────────
        @if (isGroupAuthorized([1]))
        const mergeBar     = document.getElementById('merge-bar');
        const selectedOps  = new Set();

        function updateMergeBar() {
            const n = selectedOps.size;
            if (n === 0) {
                mergeBar.style.setProperty('display', 'none', 'important');
                return;
            }
            mergeBar.style.setProperty('display', 'flex', 'important');
            const mergeBtn = document.getElementById('open-merge-btn');
            if (n === 2) {
                document.getElementById('merge-bar-label').textContent = '2 ops selected — ready to merge';
                mergeBtn.disabled = false;
            } else {
                document.getElementById('merge-bar-label').textContent =
                    n + ' op' + (n > 1 ? 's' : '') + ' selected (select exactly 2)';
                mergeBtn.disabled = true;
            }
        }

        document.querySelectorAll('.op-select').forEach(cb => {
            cb.addEventListener('change', function () {
                this.checked ? selectedOps.add(this.value) : selectedOps.delete(this.value);
                updateMergeBar();
            });
        });

        document.getElementById('clear-selection-btn').addEventListener('click', function () {
            document.querySelectorAll('.op-select:checked').forEach(cb => {
                cb.checked = false;
                selectedOps.delete(cb.value);
            });
            updateMergeBar();
        });

        document.getElementById('open-merge-btn').addEventListener('click', function () {
            if (selectedOps.size !== 2) return;
            const [opA, opB] = [...selectedOps];
            document.getElementById('merge-op-a').value       = opA;
            document.getElementById('merge-op-b').value       = opB;
            document.getElementById('merge-op-a-label').textContent = opA.substring(0, 8) + '…';
            document.getElementById('merge-op-b-label').textContent = opB.substring(0, 8) + '…';
            document.getElementById('merge-qty').value    = '';
            document.getElementById('merge-reason').value = '';
            new bootstrap.Modal(document.getElementById('merge-modal')).show();
        });
        @endif

        // ── Audit trail ───────────────────────────────────────────────────
        const actionBadge = {
            applied: 'success', accepted: 'success',
            submitted: 'primary',
            conflict_flagged: 'danger',
            discarded: 'secondary', cancelled: 'secondary', overridden: 'secondary', superseded: 'dark',
            edited: 'warning', merged: 'warning',
            reconciled: 'info',
        };
        const actionLabel = {
            submitted:        'Soumis',
            applied:          'Appliqué',
            conflict_flagged: 'Conflit détecté',
            accepted:         'Accepté',
            discarded:        'Ignoré',
            cancelled:        'Annulé',
            merged:           'Fusionné',
            edited:           'Modifié',
            overridden:       'Corrigé',
            superseded:       'Remplacé',
            reconciled:       'Réconcilié',
        };

        document.querySelectorAll('.btn-history').forEach(btn => {
            btn.addEventListener('click', function () {
                const opId = this.getAttribute('data-op-id');
                const url  = this.getAttribute('data-history-url');

                document.getElementById('history-op-id').textContent  = opId.substring(0, 8) + '…';
                document.getElementById('history-loading').style.display = '';
                document.getElementById('history-table').style.display   = 'none';
                document.getElementById('history-empty').style.display   = 'none';
                document.getElementById('history-body').innerHTML        = '';

                const modal = new bootstrap.Modal(document.getElementById('history-modal'));
                modal.show();

                fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(r => r.json())
                    .then(entries => {
                        document.getElementById('history-loading').style.display = 'none';
                        if (!entries.length) {
                            document.getElementById('history-empty').style.display = '';
                            return;
                        }
                        const tbody = document.getElementById('history-body');
                        entries.forEach((e, i) => {
                            const color   = actionBadge[e.action] || 'light';
                            const label   = actionLabel[e.action]  || e.action.replace(/_/g, ' ');
                            const hasDiff = e.before_value !== null || e.after_value !== null;
                            const diffBtn = hasDiff
                                ? `<button class="btn btn-xs btn-outline-info px-1 py-0 diff-btn"
                                        data-before='${JSON.stringify(e.before_value)}'
                                        data-after='${JSON.stringify(e.after_value)}'>
                                        <i class="fas fa-eye"></i>
                                   </button>`
                                : '—';
                            tbody.insertAdjacentHTML('beforeend', `
                                <tr>
                                    <td>${i + 1}</td>
                                    <td><span class="badge bg-${color}">${label}</span></td>
                                    <td>${e.actor}</td>
                                    <td>${e.actor_group_id}</td>
                                    <td class="small">${e.device_id}</td>
                                    <td class="small">${e.reason ?? '—'}</td>
                                    <td>${diffBtn}</td>
                                    <td class="small text-nowrap">${e.created_at ?? '—'}</td>
                                </tr>`);
                        });

                        tbody.querySelectorAll('.diff-btn').forEach(b => {
                            b.addEventListener('click', () => {
                                const before = JSON.parse(b.getAttribute('data-before') || 'null');
                                const after  = JSON.parse(b.getAttribute('data-after')  || 'null');
                                document.getElementById('hdiff-before').textContent =
                                    before !== null ? JSON.stringify(before, null, 2) : 'null';
                                document.getElementById('hdiff-after').textContent =
                                    after  !== null ? JSON.stringify(after,  null, 2) : 'null';
                                new bootstrap.Modal(document.getElementById('history-diff-modal')).show();
                            });
                        });

                        document.getElementById('history-table').style.display = '';
                    })
                    .catch(() => {
                        document.getElementById('history-loading').style.display = 'none';
                        document.getElementById('history-empty').textContent = 'Failed to load audit trail.';
                        document.getElementById('history-empty').style.display = '';
                    });
            });
        });
    </script>
    @endpush
</x-app-layout>
