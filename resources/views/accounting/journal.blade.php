<x-app-layout>
    @push('links')
    <link href="{{ asset('libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('libs/datatables.net-buttons-bs5/css/buttons.bootstrap5.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('libs/datatables.net-responsive-bs5/css/responsive.bootstrap5.min.css') }}" rel="stylesheet" type="text/css" />
    @endpush

    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">{{ __('locale.acct_accounting_journal') }}</h4>
                @if(isGroupAuthorized([1,2,3]))
                <a href="{{ route('accounting.journal.create') }}" class="btn btn-primary btn-sm">
                    <i class="fa fa-plus me-1"></i> {{ __('locale.acct_new_entry') }}
                </a>
                @endif
            </div>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    {{-- Odoo Pending Approval Banner (admin only) --}}
    @if(isGroupAuthorized([1]))
    @php $pendingOdoo = $entries->where('odoo_status','pending_admin_approval')->count(); @endphp
    @if($pendingOdoo > 0)
    <div class="alert alert-warning">
        <i class="fa fa-clock me-2"></i>
        <strong>{{ $pendingOdoo }} journal {{ Str::plural('entry', $pendingOdoo) }}</strong> pending your Odoo authorization.
    </div>
    @endif
    @endif

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <table id="datatable-buttons" class="table table-sm table-hover table-bordered table-striped dt-responsive nowrap" style="width:100%">
                        <thead>
                            <tr>
                                <th>{{ __('locale.acct_date') }}</th>
                                <th>{{ __('locale.acct_journal_col') }}</th>
                                <th>{{ __('locale.acct_reference') }}</th>
                                <th>{{ __('locale.acct_event_type') }}</th>
                                <th>{{ __('locale.acct_provenance_category') }}</th>
                                <th>{{ __('locale.acct_description') }}</th>
                                <th class="text-end">{{ __('locale.acct_debit') }}</th>
                                <th class="text-end">{{ __('locale.acct_credit') }}</th>
                                <th>{{ __('locale.acct_currency') }}</th>
                                <th>{{ __('locale.acct_send_to_odoo_q') }}</th>
                                <th>{{ __('locale.acct_odoo_status') }}</th>
                                <th>{{ __('locale.acct_comments') }}</th>
                                <th>{{ __('locale.acct_actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($entries as $entry)
                            @php
                                $statusBadge = [
                                    'draft'     => 'secondary',
                                    'submitted' => 'warning',
                                    'approved'  => 'info',
                                    'posted'    => 'success',
                                    'rejected'  => 'danger',
                                ][$entry->status] ?? 'secondary';

                                $odooBadge = [
                                    'not_queued'            => 'secondary',
                                    'pending_admin_approval'=> 'warning',
                                    'approved_for_odoo'     => 'success',
                                    'exported'              => 'info',
                                    'synced'                => 'primary',
                                    'rejected'              => 'danger',
                                ][$entry->odoo_status] ?? 'secondary';
                            @endphp
                            @php
                                $journalRows = $entry->lines->isNotEmpty() ? $entry->lines : collect([null]);
                            @endphp
                            @foreach($journalRows as $line)
                            <tr>
                                <td>{{ ($line?->line_date ?? $entry->entry_date)->format('d/m/Y') }}</td>
                                <td>{{ $line?->journal ?? $entry->journal_name ?? '—' }}</td>
                                <td>{{ $line?->document_reference ?? $entry->document_reference ?? '—' }}</td>
                                <td><span class="badge bg-light text-dark border">{{ $line?->event_type ?? $entry->event_type ?? str_replace('_',' ', $entry->entry_type) }}</span></td>
                                <td>{{ $line?->provenance_category ?? $entry->provenance_category ?? '—' }}</td>
                                <td>{{ Str::limit($line?->description ?? $entry->description, 60) }}</td>
                                <td class="text-end">{{ ($line?->debit ?? $entry->total_debit) > 0 ? number_format($line?->debit ?? $entry->total_debit, 0, '.', ' ') : '' }}</td>
                                <td class="text-end">{{ ($line?->credit ?? $entry->total_credit) > 0 ? number_format($line?->credit ?? $entry->total_credit, 0, '.', ' ') : '' }}</td>
                                <td>{{ $line?->currency ?? $entry->currency }}</td>
                                <td>
                                    @php
                                        $sendDecision = $line?->send_to_odoo ?? $entry->send_to_odoo;
                                        $sendBadge = [
                                            'Yes' => 'success',
                                            'No' => 'danger',
                                            'Hold for Finance' => 'warning',
                                            'Not applicable' => 'secondary',
                                            'To review' => 'info',
                                        ][$sendDecision] ?? 'secondary';
                                    @endphp
                                    <span class="badge bg-{{ $sendBadge }}">{{ $sendDecision ?? __('locale.acct_hold_for_finance') }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $odooBadge }}">
                                        {{ $line?->odoo_status ?? ucfirst(str_replace('_', ' ', $entry->odoo_status)) }}
                                    </span>
                                </td>
                                <td>{{ Str::limit($line?->comments ?? $entry->comments ?? '—', 60) }}</td>
                                <td>
                                    <a href="{{ route('accounting.journal.show', $entry->id) }}" class="btn btn-xs btn-label-info" title="View">
                                        <i class="fa fa-eye"></i>
                                    </a>

                                    @if($entry->status === 'draft' && ($entry->created_by === auth()->id() || isGroupAuthorized([1])))
                                    <a href="{{ route('accounting.journal.edit', $entry->id) }}" class="btn btn-xs btn-label-warning" title="{{ __('locale.acct_edit_entry') }}">
                                        <i class="fa fa-edit"></i>
                                    </a>
                                    <form action="{{ route('accounting.journal.submit', $entry->id) }}" method="POST" style="display:inline-block">
                                        @csrf
                                        <button class="btn btn-xs btn-label-warning" title="{{ __('locale.acct_submit_for_approval') }}"
                                            onclick="return confirm('Submit this entry for admin approval?')">
                                            <i class="fa fa-paper-plane"></i>
                                        </button>
                                    </form>
                                    @endif

                                    @if(isGroupAuthorized([1]))
                                        @if($entry->status === 'submitted')
                                        <form action="{{ route('accounting.journal.approve', $entry->id) }}" method="POST" style="display:inline-block">
                                            @csrf
                                            <button class="btn btn-xs btn-success" title="{{ __('locale.acct_approve_entry') }}"
                                                onclick="return confirm('Approve this journal entry?')">
                                                <i class="fa fa-check"></i>
                                            </button>
                                        </form>
                                        <form action="{{ route('accounting.journal.reject', $entry->id) }}" method="POST" style="display:inline-block">
                                            @csrf
                                            <button class="btn btn-xs btn-danger" title="{{ __('locale.acct_reject_entry') }}"
                                                onclick="return confirm('Reject this journal entry?')">
                                                <i class="fa fa-times"></i>
                                            </button>
                                        </form>
                                        @endif

                                        @if(in_array($entry->status, ['approved','posted']) && $entry->odoo_status === 'pending_admin_approval')
                                        <form action="{{ route('accounting.journal.approve-odoo', $entry->id) }}" method="POST" style="display:inline-block">
                                            @csrf
                                            <button class="btn btn-xs btn-primary" title="{{ __('locale.acct_authorize_odoo_export') }}"
                                                onclick="return confirm('Authorize this entry for Odoo export?')">
                                                <i class="fas fa-cloud-upload-alt"></i>
                                            </button>
                                        </form>
                                        <button class="btn btn-xs btn-label-danger" title="{{ __('locale.acct_block_odoo_export') }}"
                                            data-bs-toggle="modal" data-bs-target="#rejectOdooModal{{ $entry->id }}">
                                            <i class="fas fa-ban"></i>
                                        </button>
                                        {{-- Reject Odoo Modal --}}
                                        <div class="modal fade" id="rejectOdooModal{{ $entry->id }}" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form action="{{ route('accounting.journal.reject-odoo', $entry->id) }}" method="POST">
                                                        @csrf
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">{{ __('locale.acct_block_odoo_export') }} – {{ $entry->reference }}</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <label class="form-label">{{ __('locale.acct_reason_for_blocking') }}</label>
                                                            <textarea name="reason" class="form-control" rows="3" required placeholder="Explain why this entry should not be exported to Odoo..."></textarea>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('locale.acct_cancel') }}</button>
                                                            <button type="submit" class="btn btn-danger">{{ __('locale.acct_block_export') }}</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                            @endforeach
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
    <script src="{{ asset('libs/datatables.net-buttons-bs5/js/buttons.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('libs/datatables.net-responsive-bs5/js/responsive.bootstrap5.min.js') }}"></script>
    <script>
        $('#datatable-buttons').DataTable({ responsive: true, order: [[0,'desc']] });
    </script>
    @endpush
</x-app-layout>
