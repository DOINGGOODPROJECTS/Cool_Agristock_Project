<x-app-layout>
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">{{ __('locale.acct_journal_entry') }} — {{ $entry->reference }}</h4>
                <div class="d-flex gap-2">
                    @if($entry->status === 'draft')
                    <a href="{{ route('accounting.journal.edit', $entry->id) }}" class="btn btn-warning btn-sm">
                        <i class="fa fa-edit me-1"></i> {{ __('locale.acct_edit_entry') }}
                    </a>
                    @endif
                    <a href="{{ route('accounting.journal.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fa fa-arrow-left me-1"></i> {{ __('locale.acct_back') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="row">
        <div class="col-lg-8">

            {{-- Entry Details --}}
            <div class="card">
                <div class="card-header"><h6 class="mb-0">{{ __('locale.acct_entry_details') }}</h6></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="text-muted small">{{ __('locale.acct_reference') }}</label>
                            <p class="fw-semibold mb-0">{{ $entry->reference }}</p>
                        </div>
                        <div class="col-sm-6">
                            <label class="text-muted small">{{ __('locale.acct_date') }}</label>
                            <p class="fw-semibold mb-0">{{ $entry->entry_date->format('d/m/Y') }}</p>
                        </div>
                        <div class="col-sm-6">
                            <label class="text-muted small">{{ __('locale.acct_created_by') }}</label>
                            <p class="mb-0">{{ $entry->createdBy->name ?? '—' }}</p>
                        </div>
                        <div class="col-sm-6">
                            <label class="text-muted small">{{ __('locale.acct_total_lines') }}</label>
                            <p class="mb-0">{{ $entry->lines->count() }} line(s)</p>
                        </div>
                        <div class="col-sm-6">
                            <label class="text-muted small">{{ __('locale.acct_currency') }}</label>
                            <p class="mb-0">{{ $entry->currency }}</p>
                        </div>
                        @if($entry->financialEvent)
                        <div class="col-12">
                            <label class="text-muted small">{{ __('locale.acct_linked_financial_event') }}</label>
                            <p class="mb-0 font-monospace">{{ $entry->financialEvent->canonical_id ?? $entry->financial_event_id }}</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Journal Lines --}}
            <div class="card">
                <div class="card-header"><h6 class="mb-0">{{ __('locale.acct_journal_lines') }}</h6></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('locale.acct_date') }}</th>
                                    <th>{{ __('locale.acct_journal_col') }}</th>
                                    <th>{{ __('locale.acct_reference') }}</th>
                                    <th>{{ __('locale.acct_event_type') }}</th>
                                    <th>{{ __('locale.acct_provenance_category') }}</th>
                                    <th>{{ __('locale.acct_description') }}</th>
                                    <th class="text-end">{{ __('locale.acct_debit') }} (XOF)</th>
                                    <th class="text-end">{{ __('locale.acct_credit') }} (XOF)</th>
                                    <th>{{ __('locale.acct_currency') }}</th>
                                    <th>{{ __('locale.acct_send_to_odoo_q') }}</th>
                                    <th>{{ __('locale.acct_odoo_status') }}</th>
                                    <th>{{ __('locale.acct_comments') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($entry->lines as $line)
                                <tr>
                                    <td>{{ $line->line_date?->format('d/m/Y') ?? $entry->entry_date->format('d/m/Y') }}</td>
                                    <td>{{ $line->journal ?: '—' }}</td>
                                    <td>{{ $line->document_reference ?: '—' }}</td>
                                    <td>{{ $line->event_type ?: '—' }}</td>
                                    <td>{{ $line->provenance_category ?: '—' }}</td>
                                    <td>{{ $line->description ?: $line->label }}</td>
                                    <td class="text-end">{{ $line->debit > 0 ? number_format($line->debit, 0, '.', ' ') : '' }}</td>
                                    <td class="text-end">{{ $line->credit > 0 ? number_format($line->credit, 0, '.', ' ') : '' }}</td>
                                    <td>{{ $line->currency ?: $entry->currency }}</td>
                                    <td>{{ $line->send_to_odoo ?: __('locale.acct_hold_for_finance') }}</td>
                                    <td>{{ $line->odoo_status ?: __('locale.acct_not_exported') }}</td>
                                    <td>{{ $line->comments ?: '—' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="{{ $entry->isBalanced() ? 'table-success' : 'table-danger' }}">
                                <tr>
                                    <td colspan="6" class="fw-bold text-end">
                                        @if($entry->isBalanced())
                                            <i class="fa fa-check-circle me-1"></i> {{ __('locale.acct_balanced') }}
                                        @else
                                            <i class="fa fa-exclamation-triangle me-1"></i> {{ __('locale.acct_not_balanced') }}
                                        @endif
                                    </td>
                                    <td class="text-end fw-bold">{{ number_format($entry->total_debit, 0, '.', ' ') }}</td>
                                    <td class="text-end fw-bold">{{ number_format($entry->total_credit, 0, '.', ' ') }}</td>
                                    <td colspan="4"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

        </div>

        <div class="col-lg-4">

            {{-- Status Card --}}
            <div class="card">
                <div class="card-header"><h6 class="mb-0">{{ __('locale.acct_status') }}</h6></div>
                <div class="card-body">
                    @php
                        $statusBadge = ['draft'=>'secondary','submitted'=>'warning','approved'=>'info','posted'=>'success','rejected'=>'danger'][$entry->status] ?? 'secondary';
                        $odooBadge = ['not_queued'=>'secondary','pending_admin_approval'=>'warning','approved_for_odoo'=>'success','exported'=>'info','synced'=>'primary','rejected'=>'danger'][$entry->odoo_status] ?? 'secondary';
                    @endphp
                    <p class="mb-2">
                        <span class="text-muted small d-block">{{ __('locale.acct_entry_status') }}</span>
                        <span class="badge bg-{{ $statusBadge }} fs-6">{{ ucfirst($entry->status) }}</span>
                    </p>
                    <p class="mb-2">
                        <span class="text-muted small d-block">{{ __('locale.acct_odoo_status') }}</span>
                        <span class="badge bg-{{ $odooBadge }}">{{ ucfirst(str_replace('_',' ',$entry->odoo_status)) }}</span>
                    </p>
                    <p class="mb-2">
                        <span class="text-muted small d-block">{{ __('locale.acct_send_to_odoo_q') }}</span>
                        <span class="badge bg-info">{{ $entry->send_to_odoo ?? __('locale.acct_hold_for_finance') }}</span>
                    </p>
                    @if($entry->approvedBy)
                    <p class="mb-2">
                        <span class="text-muted small d-block">{{ __('locale.acct_approved_by') }}</span>
                        {{ $entry->approvedBy->name }} — {{ $entry->approved_at?->format('d/m/Y H:i') }}
                    </p>
                    @endif
                    @if($entry->postedBy)
                    <p class="mb-2">
                        <span class="text-muted small d-block">{{ __('locale.acct_posted_by') }}</span>
                        {{ $entry->postedBy->name }} — {{ $entry->posted_at?->format('d/m/Y H:i') }}
                    </p>
                    @endif

                    @if($entry->odoo_rejection_reason)
                    <div class="alert alert-danger mt-2 mb-0 py-2">
                        <strong>{{ __('locale.acct_odoo_rejection_reason') }}:</strong><br>
                        {{ $entry->odoo_rejection_reason }}
                    </div>
                    @endif
                </div>
            </div>

            {{-- Odoo Verification Card --}}
            @if($entry->odoo_status === 'exported' && $entry->odoo_move_id)
            <div class="card border-success">
                <div class="card-header bg-success bg-opacity-10 d-flex align-items-center justify-content-between">
                    <h6 class="mb-0 text-success"><i class="fa fa-check-circle me-1"></i> {{ __('locale.acct_confirmed_in_odoo') }}</h6>
                    <a href="{{ config('odoo.url') }}/odoo/accounting/journal-entries/{{ $entry->odoo_move_id }}"
                       target="_blank" class="btn btn-xs btn-success">
                        <i class="fa fa-external-link-alt me-1"></i> {{ __('locale.acct_open_in_odoo') }}
                    </a>
                </div>
                <div class="card-body pb-2">
                    <p class="mb-2">
                        <span class="text-muted small d-block">{{ __('locale.acct_odoo_move_reference') }}</span>
                        <strong class="font-monospace">{{ $entry->odoo_move_name ?? '—' }}</strong>
                    </p>
                    <p class="mb-2">
                        <span class="text-muted small d-block">{{ __('locale.acct_odoo_move_id') }}</span>
                        <span class="font-monospace">#{{ $entry->odoo_move_id }}</span>
                    </p>
                    @if($entry->odoo_approved_at)
                    <p class="mb-2">
                        <span class="text-muted small d-block">{{ __('locale.acct_pushed_at') }}</span>
                        {{ $entry->odoo_approved_at->format('d/m/Y H:i') }}
                    </p>
                    @endif
                </div>
            </div>
            @endif

            @if($entry->odoo_push_error)
            <div class="alert alert-danger">
                <strong><i class="fa fa-exclamation-triangle me-1"></i> {{ __('locale.acct_last_odoo_push_error') }}</strong>
                <p class="mb-0 mt-1 small">{{ $entry->odoo_push_error }}</p>
            </div>
            @endif

            {{-- Actions --}}
            <div class="card">
                <div class="card-header"><h6 class="mb-0">{{ __('locale.acct_actions') }}</h6></div>
                <div class="card-body d-grid gap-2">

                    @if($entry->status === 'draft' && ($entry->created_by === auth()->id() || isGroupAuthorized([1])))
                    <form action="{{ route('accounting.journal.submit', $entry->id) }}" method="POST">
                        @csrf
                        <button class="btn btn-warning w-100" onclick="return confirm('Submit for admin approval?')">
                            <i class="fa fa-paper-plane me-1"></i> {{ __('locale.acct_submit_for_approval') }}
                        </button>
                    </form>
                    @endif

                    @if(isGroupAuthorized([1]))

                        @if($entry->status === 'submitted')
                        <form action="{{ route('accounting.journal.approve', $entry->id) }}" method="POST">
                            @csrf
                            <button class="btn btn-success w-100" onclick="return confirm('Approve this entry?')">
                                <i class="fa fa-check me-1"></i> {{ __('locale.acct_approve_entry') }}
                            </button>
                        </form>
                        <form action="{{ route('accounting.journal.reject', $entry->id) }}" method="POST">
                            @csrf
                            <button class="btn btn-danger w-100" onclick="return confirm('Reject this entry?')">
                                <i class="fa fa-times me-1"></i> {{ __('locale.acct_reject_entry') }}
                            </button>
                        </form>
                        @endif

                        @if(in_array($entry->status, ['approved','posted']) && $entry->odoo_status === 'pending_admin_approval')
                        <hr class="my-1">
                        <p class="text-muted small mb-1"><strong>{{ __('locale.acct_odoo_export_auth') }}</strong></p>
                        <form action="{{ route('accounting.journal.approve-odoo', $entry->id) }}" method="POST">
                            @csrf
                            <button class="btn btn-primary w-100" onclick="return confirm('Authorize this entry for Odoo export?')">
                                <i class="fas fa-cloud-upload-alt me-1"></i> {{ __('locale.acct_authorize_odoo_export') }}
                            </button>
                        </form>
                        <button class="btn btn-outline-danger w-100" data-bs-toggle="modal" data-bs-target="#rejectOdooModal">
                            <i class="fas fa-ban me-1"></i> {{ __('locale.acct_block_odoo_export') }}
                        </button>

                        {{-- Reject Odoo Modal --}}
                        <div class="modal fade" id="rejectOdooModal" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form action="{{ route('accounting.journal.reject-odoo', $entry->id) }}" method="POST">
                                        @csrf
                                        <div class="modal-header">
                                            <h5 class="modal-title">{{ __('locale.acct_block_odoo_export') }}</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <label class="form-label">{{ __('locale.acct_reason_for_blocking') }}</label>
                                            <textarea name="reason" class="form-control" rows="3" required
                                                placeholder="Explain why this entry should not be exported to Odoo..."></textarea>
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
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
