<x-app-layout>
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">{{ __('locale.acct_journal_ledger') }}</h4>
                <div class="d-flex gap-2">
                    <a href="{{ route('accounting.journal.index') }}" class="btn btn-sm btn-secondary">
                        <i class="fa fa-table me-1"></i> {{ __('locale.acct_table_view') }}
                    </a>
                    <a href="{{ route('accounting.journal.create') }}" class="btn btn-sm btn-primary">
                        <i class="fa fa-plus me-1"></i> {{ __('locale.acct_new_entry') }}
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

    @if($entries->isEmpty())
    <div class="card"><div class="card-body text-center text-muted py-5">{{ __('locale.acct_no_journal_yet') }}</div></div>
    @endif

    @foreach($entries as $date => $dayEntries)
    @php
        $dayDebit  = $dayEntries->sum(fn($e) => $e->total_debit);
        $dayCredit = $dayEntries->sum(fn($e) => $e->total_credit);
    @endphp

    {{-- Date group header --}}
    <div class="d-flex align-items-center gap-3 mb-2 mt-3">
        <h6 class="mb-0 text-muted fw-semibold">
            <i class="fa fa-calendar-day me-1"></i>
            {{ \Carbon\Carbon::parse($date)->format('d F Y') }}
        </h6>
        <span class="badge bg-light text-dark border">{{ $dayEntries->count() }} {{ Str::plural('entry', $dayEntries->count()) }}</span>
        <span class="text-muted small">
            DR {{ number_format($dayDebit, 0, '.', ' ') }} &nbsp;|&nbsp;
            CR {{ number_format($dayCredit, 0, '.', ' ') }} XOF
        </span>
        <hr class="flex-grow-1 my-0">
    </div>

    @foreach($dayEntries as $entry)
    @php
        $odooBadge = [
            'not_queued'             => ['secondary', 'Not queued'],
            'pending_admin_approval' => ['warning',   'Pending'],
            'approved_for_odoo'      => ['info',      'Approved for Odoo'],
            'exported'               => ['success',   'Exported'],
            'synced'                 => ['primary',   'Synced'],
            'rejected'               => ['danger',    'Rejected'],
        ][$entry->odoo_status] ?? ['secondary', ucfirst($entry->odoo_status)];

        $statusBadge = [
            'draft'     => 'secondary',
            'submitted' => 'warning',
            'approved'  => 'info',
            'posted'    => 'success',
            'rejected'  => 'danger',
        ][$entry->status] ?? 'secondary';
    @endphp

    <div class="card mb-2 border-start border-4 border-{{ $odooBadge[0] }}">
        {{-- Entry header row (clickable to expand) --}}
        <div class="card-body py-2 px-3 d-flex align-items-center gap-3 journal-entry-toggle"
             data-bs-toggle="collapse"
             data-bs-target="#lines-{{ $entry->id }}"
             style="cursor:pointer">

            <i class="fa fa-chevron-right toggle-icon text-muted" style="font-size:11px; transition:transform .2s"></i>

            <span class="fw-semibold font-monospace text-primary" style="min-width:175px">
                {{ $entry->reference }}
            </span>

            <span class="badge bg-{{ $statusBadge }}">{{ ucfirst($entry->status) }}</span>
            <span class="badge bg-{{ $odooBadge[0] }}">{{ $odooBadge[1] }}</span>

            <span class="text-muted small ms-1">
                {{ $entry->lines_count ?? $entry->lines->count() }} line(s)
            </span>

            <span class="ms-auto text-muted small d-flex gap-3">
                <span>DR <strong>{{ number_format($entry->total_debit, 0, '.', ' ') }}</strong></span>
                <span>CR <strong>{{ number_format($entry->total_credit, 0, '.', ' ') }}</strong></span>
                @if(abs($entry->total_debit - $entry->total_credit) < 0.01)
                <span class="text-success"><i class="fa fa-check-circle"></i> {{ __('locale.acct_balanced') }}</span>
                @else
                <span class="text-danger"><i class="fa fa-exclamation-triangle"></i> {{ __('locale.acct_unbalanced') }}</span>
                @endif
                <span>{{ $entry->currency }}</span>
            </span>

            <a href="{{ route('accounting.journal.show', $entry->id) }}"
               class="btn btn-xs btn-label-info ms-2"
               title="View full detail"
               onclick="event.stopPropagation()">
                <i class="fa fa-external-link-alt"></i>
            </a>

            @if($entry->status === 'draft')
            <a href="{{ route('accounting.journal.edit', $entry->id) }}"
               class="btn btn-xs btn-label-warning ms-1"
               title="{{ __('locale.acct_edit_entry') }}"
               onclick="event.stopPropagation()">
                <i class="fa fa-edit"></i>
            </a>
            @endif

            @if(in_array($entry->status, ['draft', 'rejected']))
            <form action="{{ route('accounting.journal.destroy', $entry->id) }}"
                  method="POST" style="display:inline-block"
                  onclick="event.stopPropagation()">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="btn btn-xs btn-danger ms-1"
                        title="Delete entry"
                        onclick="return confirm('Delete {{ $entry->reference }}? This cannot be undone.')">
                    <i class="fa fa-trash"></i>
                </button>
            </form>
            @endif
        </div>

        {{-- Collapsible lines --}}
        <div class="collapse" id="lines-{{ $entry->id }}">
            <div class="px-3 pb-3 pt-0">
                @if($entry->lines->isEmpty())
                <p class="text-muted small mb-0">{{ __('locale.acct_no_lines_recorded') }}</p>
                @else
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('locale.acct_date') }}</th>
                                <th>{{ __('locale.acct_journal_col') }}</th>
                                <th>{{ __('locale.acct_reference') }}</th>
                                <th>{{ __('locale.acct_event_type') }}</th>
                                <th>{{ __('locale.acct_provenance') }}</th>
                                <th>{{ __('locale.acct_description') }}</th>
                                <th class="text-end">{{ __('locale.acct_debit') }} (XOF)</th>
                                <th class="text-end">{{ __('locale.acct_credit') }} (XOF)</th>
                                <th>{{ __('locale.acct_send_to_odoo_q') }}</th>
                                <th>{{ __('locale.acct_odoo_status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($entry->lines as $line)
                            @php
                                $lineSendBadge = ['Yes' => 'success', 'No' => 'danger', 'To review' => 'warning'][$line->send_to_odoo] ?? 'secondary';
                                $lineOdooBadge = $line->odoo_status === 'Exported' ? 'success' : ($line->odoo_status === 'In review' ? 'warning' : 'secondary');
                            @endphp
                            <tr>
                                <td class="text-nowrap">{{ $line->line_date?->format('d/m/Y') ?? '—' }}</td>
                                <td>{{ $line->journal ?: '—' }}</td>
                                <td>{{ $line->document_reference ?: '—' }}</td>
                                <td><span class="badge bg-light text-dark border small">{{ $line->event_type ?: '—' }}</span></td>
                                <td>{{ $line->provenance_category ?: '—' }}</td>
                                <td>{{ $line->description ?: $line->label }}</td>
                                <td class="text-end fw-semibold">{{ $line->debit > 0 ? number_format($line->debit, 0, '.', ' ') : '' }}</td>
                                <td class="text-end fw-semibold">{{ $line->credit > 0 ? number_format($line->credit, 0, '.', ' ') : '' }}</td>
                                <td><span class="badge bg-{{ $lineSendBadge }}">{{ $line->send_to_odoo }}</span></td>
                                <td><span class="badge bg-{{ $lineOdooBadge }}">{{ $line->odoo_status }}</span></td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="6" class="text-end fw-bold">{{ __('locale.acct_totals') }}</td>
                                <td class="text-end fw-bold">{{ number_format($entry->total_debit, 0, '.', ' ') }}</td>
                                <td class="text-end fw-bold">{{ number_format($entry->total_credit, 0, '.', ' ') }}</td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endforeach
    @endforeach

    @push('scripts')
    <script>
    document.querySelectorAll('.journal-entry-toggle').forEach(function(el) {
        el.addEventListener('click', function() {
            const icon = this.querySelector('.toggle-icon');
            const target = document.querySelector(this.getAttribute('data-bs-target'));
            if (!target) return;
            target.addEventListener('shown.bs.collapse',  () => icon.style.transform = 'rotate(90deg)');
            target.addEventListener('hidden.bs.collapse', () => icon.style.transform = 'rotate(0deg)');
        });
    });
    </script>
    @endpush
</x-app-layout>
