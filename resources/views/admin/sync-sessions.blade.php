<x-app-layout>
    @push('links')
    <link href="{{ asset('libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('libs/datatables.net-buttons-bs5/css/buttons.bootstrap5.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('libs/datatables.net-responsive-bs5/css/responsive.bootstrap5.min.css') }}" rel="stylesheet" type="text/css" />
    @endpush

    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Sync Sessions</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('locale.dashboard')</a></li>
                        <li class="breadcrumb-item active">Sync Sessions</li>
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
                            <th>#</th>
                            <th>Session ID</th>
                            <th>User</th>
                            <th>Device</th>
                            <th>Submitted</th>
                            <th>Applied</th>
                            <th>Conflicted</th>
                            <th>Conflict Rate</th>
                            <th>Duration</th>
                            <th>Logical Seq</th>
                            <th>Status</th>
                            <th>Started At</th>
                            <th>Actions</th>
                        </thead>
                        <tbody>
                            @foreach ($sessions as $session)
                            @php
                                $badge = match($session->status) {
                                    'completed'   => 'success',
                                    'in_progress' => 'warning',
                                    'failed'      => 'danger',
                                    default       => 'secondary',
                                };

                                $submitted   = (int) $session->ops_submitted;
                                $applied     = (int) $session->ops_applied;
                                $conflicted  = (int) $session->ops_conflicted;
                                $total       = $applied + $conflicted;
                                $appliedPct  = $total > 0 ? round($applied / $total * 100) : ($submitted > 0 ? 100 : 0);
                                $conflictPct = $total > 0 ? round($conflicted / $total * 100) : 0;

                                $startedAt  = $session->created_at;
                                $endedAt    = $session->updated_at ?? $session->created_at;
                                $secs       = max(0, $startedAt->diffInSeconds($endedAt));
                                $duration   = $secs < 60
                                    ? $secs . 's'
                                    : ($secs < 3600
                                        ? round($secs / 60, 1) . 'm'
                                        : round($secs / 3600, 1) . 'h');
                            @endphp
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td><span class="font-monospace small" title="{{ $session->session_id }}">{{ substr($session->session_id, 0, 8) }}…</span></td>
                                <td>{{ $session->user->name ?? '—' }}</td>
                                <td>{{ $session->device_id }}</td>
                                <td>{{ $submitted }}</td>
                                <td>{{ $applied }}</td>
                                <td>
                                    @if ($conflicted > 0)
                                    <span class="badge bg-danger">{{ $conflicted }}</span>
                                    @else
                                    0
                                    @endif
                                </td>
                                <td style="min-width:120px">
                                    @if ($submitted > 0)
                                    <div class="progress" style="height:16px" title="{{ $appliedPct }}% applied, {{ $conflictPct }}% conflicted">
                                        <div class="progress-bar bg-success" style="width:{{ $appliedPct }}%" title="{{ $applied }} applied">
                                            @if ($appliedPct >= 20)<span class="small">{{ $appliedPct }}%</span>@endif
                                        </div>
                                        @if ($conflictPct > 0)
                                        <div class="progress-bar bg-danger" style="width:{{ $conflictPct }}%" title="{{ $conflicted }} conflicted">
                                            @if ($conflictPct >= 20)<span class="small">{{ $conflictPct }}%</span>@endif
                                        </div>
                                        @endif
                                    </div>
                                    @else
                                    <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td class="small">{{ $duration }}</td>
                                <td>{{ $session->client_logical_seq }}</td>
                                <td><span class="badge bg-{{ $badge }}">{{ str_replace('_', ' ', $session->status) }}</span></td>
                                <td>{{ date('d/m/Y H:i:s', strtotime($session->created_at)) }}</td>
                                <td>
                                    <a href="{{ route('inventory-ops.index') }}" class="btn btn-sm btn-outline-secondary" title="View ops">
                                        <i class="fas fa-list"></i>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
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
    @endpush
</x-app-layout>
