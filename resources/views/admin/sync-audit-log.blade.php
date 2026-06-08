<x-app-layout>
    @push('links')
    <link href="{{ asset('libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('libs/datatables.net-buttons-bs5/css/buttons.bootstrap5.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('libs/datatables.net-responsive-bs5/css/responsive.bootstrap5.min.css') }}" rel="stylesheet" type="text/css" />
    @endpush

    @php
    $actionLabels = [
        'submitted'        => __('locale.action_submitted'),
        'applied'          => __('locale.action_applied'),
        'conflict_flagged' => __('locale.action_conflict_flagged'),
        'accepted'         => __('locale.action_accepted'),
        'discarded'        => __('locale.action_discarded'),
        'cancelled'        => __('locale.action_cancelled'),
        'merged'           => __('locale.action_merged'),
        'edited'           => __('locale.action_edited'),
        'overridden'       => __('locale.action_overridden'),
        'superseded'       => __('locale.action_superseded'),
        'reconciled'       => __('locale.action_reconciled'),
    ];
    $actionBadge = [
        'applied'          => 'success',
        'accepted'         => 'success',
        'submitted'        => 'primary',
        'conflict_flagged' => 'danger',
        'discarded'        => 'secondary',
        'cancelled'        => 'secondary',
        'overridden'       => 'secondary',
        'superseded'       => 'dark',
        'edited'           => 'warning',
        'merged'           => 'warning',
        'reconciled'       => 'info',
    ];
    @endphp

    {{-- Page title --}}
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Journal d'audit — Synchronisation</h4>
                <div class="page-title-right d-flex align-items-center gap-2">
                    {{-- Export CSV — admin only --}}
                    @if (isGroupAuthorized([1]))
                    <a href="{{ route('sync-audit-log.export', array_filter($filters ?? [])) }}"
                       class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-file-csv me-1"></i> Exporter CSV
                    </a>
                    @endif
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('locale.dashboard')</a></li>
                        <li class="breadcrumb-item active">Journal d'audit</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter form --}}
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body py-2">
                    <form method="GET" action="{{ route('sync-audit-log.index') }}" class="row g-2 align-items-end">

                        <div class="col-sm-6 col-md-3 col-xl-2">
                            <label class="form-label small mb-1">Action</label>
                            <select name="action" class="form-select form-select-sm">
                                <option value="">— Toutes —</option>
                                @foreach ($actions as $a)
                                <option value="{{ $a }}" {{ ($filters['action'] ?? '') === $a ? 'selected' : '' }}>
                                    {{ $actionLabels[$a] ?? $a }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-sm-6 col-md-3 col-xl-2">
                            <label class="form-label small mb-1">Utilisateur</label>
                            <select name="user_id" class="form-select form-select-sm">
                                <option value="">— Tous —</option>
                                @foreach ($users as $u)
                                <option value="{{ $u->id }}" {{ ($filters['user_id'] ?? '') == $u->id ? 'selected' : '' }}>
                                    {{ $u->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-sm-6 col-md-3 col-xl-2">
                            <label class="form-label small mb-1">Entrepôt</label>
                            <select name="storage_id" class="form-select form-select-sm">
                                <option value="">— Tous —</option>
                                @foreach ($storages as $s)
                                <option value="{{ $s->id }}" {{ ($filters['storage_id'] ?? '') == $s->id ? 'selected' : '' }}>
                                    {{ $s->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-sm-6 col-md-3 col-xl-2">
                            <label class="form-label small mb-1">Produit</label>
                            <select name="product_id" class="form-select form-select-sm">
                                <option value="">— Tous —</option>
                                @foreach ($products as $p)
                                <option value="{{ $p->id }}" {{ ($filters['product_id'] ?? '') == $p->id ? 'selected' : '' }}>
                                    {{ $p->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-sm-6 col-md-3 col-xl-2">
                            <label class="form-label small mb-1">Du</label>
                            <input type="date" name="date_from" class="form-control form-control-sm"
                                   value="{{ $filters['date_from'] ?? '' }}">
                        </div>

                        <div class="col-sm-6 col-md-3 col-xl-2">
                            <label class="form-label small mb-1">Au</label>
                            <input type="date" name="date_to" class="form-control form-control-sm"
                                   value="{{ $filters['date_to'] ?? '' }}">
                        </div>

                        <div class="col-auto">
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="fas fa-filter me-1"></i> Filtrer
                            </button>
                            <a href="{{ route('sync-audit-log.index') }}" class="btn btn-sm btn-outline-secondary ms-1">
                                <i class="fas fa-times"></i>
                            </a>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Log table --}}
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <table id="datatable-buttons" class="table table-hover table-bordered table-striped dt-responsive nowrap"
                           style="border-collapse:collapse;border-spacing:0;width:100%">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Op ID</th>
                                <th>Acteur</th>
                                <th>Groupe</th>
                                <th>Action</th>
                                <th>Appareil</th>
                                <th>IP</th>
                                <th>Motif</th>
                                <th>Date</th>
                                <th>Détails</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($logs as $log)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    <span class="font-monospace small" title="{{ $log->op_id }}">
                                        {{ substr($log->op_id, 0, 8) }}…
                                    </span>
                                </td>
                                <td>{{ $log->actor->name ?? '—' }}</td>
                                <td>{{ $log->actor_group_id }}</td>
                                <td>
                                    <span class="badge bg-{{ $actionBadge[$log->action] ?? 'light' }}">
                                        {{ $actionLabels[$log->action] ?? $log->action }}
                                    </span>
                                </td>
                                <td class="small">{{ $log->device_id }}</td>
                                <td class="small">{{ $log->ip_address ?? '—' }}</td>
                                <td class="small">
                                    {{ $log->reason ? \Illuminate\Support\Str::limit($log->reason, 40) : '—' }}
                                </td>
                                <td class="small text-nowrap">
                                    {{ $log->created_at ? date('d/m/Y H:i:s', strtotime($log->created_at)) : '—' }}
                                </td>
                                <td>
                                    @if ($log->before_value || $log->after_value)
                                    <button class="btn btn-sm btn-label-info"
                                        data-bs-toggle="modal" data-bs-target="#diff-modal"
                                        data-before="{{ json_encode($log->before_value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}"
                                        data-after="{{ json_encode($log->after_value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}"
                                        data-reason="{{ $log->reason }}">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    @else
                                    —
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Before/After diff modal --}}
    <div class="modal fade" id="diff-modal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary-subtle">
                    <h5 class="modal-title">@lang('locale.diff_modal_title')</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="diff-reason-row" class="alert alert-warning py-2 small mb-3" style="display:none">
                        <i class="fas fa-comment-alt me-1"></i>
                        <strong>@lang('locale.diff_reason') :</strong> <span id="diff-reason-text"></span>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <h6 class="text-muted">@lang('locale.diff_before')</h6>
                            <pre id="before-val" class="bg-light p-2 rounded small" style="min-height:60px;white-space:pre-wrap"></pre>
                        </div>
                        <div class="col-6">
                            <h6 class="text-muted">@lang('locale.diff_after')</h6>
                            <pre id="after-val" class="bg-light p-2 rounded small" style="min-height:60px;white-space:pre-wrap"></pre>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">@lang('locale.btn_close')</button>
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
        document.getElementById('diff-modal').addEventListener('show.bs.modal', function (e) {
            const btn    = e.relatedTarget;
            const reason = btn.getAttribute('data-reason');
            const reasonRow = document.getElementById('diff-reason-row');
            if (reason) {
                document.getElementById('diff-reason-text').textContent = reason;
                reasonRow.style.display = '';
            } else {
                reasonRow.style.display = 'none';
            }
            const noData = @json(__('locale.diff_no_data'));
            document.getElementById('before-val').textContent = btn.getAttribute('data-before') || noData;
            document.getElementById('after-val').textContent  = btn.getAttribute('data-after')  || noData;
        });
    </script>
    @endpush
</x-app-layout>
