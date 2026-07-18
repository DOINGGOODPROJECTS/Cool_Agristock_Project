<x-app-layout>
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">{{ __('locale.acct_invoice_ledger') }}</h4>
                @if(isGroupAuthorized([1,2,3]))
                <div class="d-flex gap-2">
                    <a href="{{ route('accounting.invoices.create') }}" class="btn btn-primary btn-sm">
                        <i class="fa fa-plus me-1"></i> {{ __('locale.acct_invoice_generator') }}
                    </a>
                    <button class="btn btn-label-primary btn-sm" data-bs-toggle="modal" data-bs-target="#generateInvoiceModal">
                        <i class="fa fa-file-invoice me-1"></i> {{ __('locale.acct_from_billing') }}
                    </button>
                </div>
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

    @if($invoices->isEmpty())
    <div class="card"><div class="card-body text-center text-muted py-5">{{ __('locale.acct_no_invoices_yet') }}. {{ __('locale.acct_invoice_generator') }}.</div></div>
    @endif

    @foreach($invoices as $date => $dayInvoices)
    @php
        $dayTotal = $dayInvoices->sum('total_amount');
    @endphp

    <div class="d-flex align-items-center gap-3 mb-2 mt-3">
        <h6 class="mb-0 text-muted fw-semibold">
            <i class="fa fa-calendar-day me-1"></i>
            {{ \Carbon\Carbon::parse($date)->format('d F Y') }}
        </h6>
        <span class="badge bg-light text-dark border">{{ $dayInvoices->count() }} {{ Str::plural('invoice', $dayInvoices->count()) }}</span>
        <span class="text-muted small">Total: {{ number_format($dayTotal, 0, '.', ' ') }} XOF</span>
        <hr class="flex-grow-1 my-0">
    </div>

    @foreach($dayInvoices as $inv)
    @php
        $customerLabel = $inv->customer->name ?? $inv->customer_name ?? '—';
        $odooBadge = match($inv->odoo_status ?? 'not_exported') {
            'exported'     => ['success',   'Exported'],
            'not_exported' => ['secondary', __('locale.acct_not_exported')],
            default        => ['warning',   ucfirst(str_replace('_',' ', $inv->odoo_status ?? ''))],
        };
        $statusBadge = ['draft'=>'secondary','issued'=>'primary','paid'=>'success','cancelled'=>'danger'][$inv->status] ?? 'secondary';
    @endphp

    <div class="card mb-2 border-start border-4 border-{{ $odooBadge[0] }}">
        <div class="card-body py-2 px-3 d-flex align-items-center gap-3 invoice-toggle"
             data-bs-toggle="collapse"
             data-bs-target="#inv-lines-{{ $inv->id }}"
             style="cursor:pointer">

            <i class="fa fa-chevron-right toggle-icon text-muted" style="font-size:11px;transition:transform .2s"></i>

            <span class="fw-semibold font-monospace text-primary" style="min-width:180px">
                {{ $inv->invoice_number }}
            </span>

            <span class="text-muted" style="min-width:160px">
                <i class="fa fa-user me-1"></i>{{ $customerLabel }}
            </span>

            <span class="badge bg-{{ $statusBadge }}">{{ ucfirst($inv->status) }}</span>
            <span class="badge bg-{{ $odooBadge[0] }}">
                {{ $inv->odoo_invoice_name ?? $odooBadge[1] }}
            </span>

            <span class="text-muted small">{{ $inv->lines->count() }} {{ Str::plural('line', $inv->lines->count()) }}</span>

            <span class="ms-auto fw-semibold">{{ number_format($inv->total_amount, 0, '.', ' ') }} XOF</span>

            <a href="{{ route('accounting.invoices.pdf', $inv->id) }}"
               class="btn btn-xs btn-label-secondary ms-1"
               title="Download PDF"
               onclick="event.stopPropagation()">
                <i class="fa fa-file-pdf"></i>
            </a>

            <a href="{{ route('accounting.invoices.show', $inv->id) }}"
               class="btn btn-xs btn-label-info ms-1"
               title="View detail"
               onclick="event.stopPropagation()">
                <i class="fa fa-external-link-alt"></i>
            </a>

            @if($inv->status === 'draft')
            <a href="{{ route('accounting.invoices.edit', $inv->id) }}"
               class="btn btn-xs btn-label-warning ms-1"
               title="{{ __('locale.acct_edit_invoice') }}"
               onclick="event.stopPropagation()">
                <i class="fa fa-edit"></i>
            </a>
            <form action="{{ route('accounting.invoices.destroy', $inv->id) }}"
                  method="POST" style="display:inline-block"
                  onclick="event.stopPropagation()">
                @csrf @method('DELETE')
                <button type="submit"
                        class="btn btn-xs btn-danger ms-1"
                        onclick="return confirm('Delete {{ $inv->invoice_number }}? This cannot be undone.')">
                    <i class="fa fa-trash"></i>
                </button>
            </form>
            @endif
        </div>

        <div class="collapse" id="inv-lines-{{ $inv->id }}">
            <div class="px-3 pb-3 pt-0">
                @if($inv->lines->isEmpty())
                <p class="text-muted small mb-0">{{ __('locale.acct_no_lines_recorded') }}</p>
                @else
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('locale.acct_event_type') }}</th>
                                <th>{{ __('locale.acct_category') }}</th>
                                <th>{{ __('locale.acct_product') }}</th>
                                <th>{{ __('locale.acct_description') }}</th>
                                <th>{{ __('locale.acct_unit') }}</th>
                                <th class="text-end">{{ __('locale.qty') }}</th>
                                <th class="text-end">{{ __('locale.acct_unit_price') }}</th>
                                <th class="text-end">{{ __('locale.acct_discount') }}</th>
                                <th class="text-end">{{ __('locale.acct_amount_before_vat') }}</th>
                                <th class="text-end">{{ __('locale.acct_vat_rate') }}%</th>
                                <th class="text-end">{{ __('locale.acct_vat_amount') }}</th>
                                <th class="text-end">{{ __('locale.acct_total') }}</th>
                                <th>{{ __('locale.acct_journal_entry_no') }}</th>
                                <th>{{ __('locale.acct_send_to_odoo_q') }}</th>
                                <th>{{ __('locale.acct_comments') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($inv->lines as $line)
                            @php
                                $sendBadge = ['Yes'=>'success','No'=>'danger','To review'=>'warning'][$line->send_to_odoo] ?? 'secondary';
                            @endphp
                            <tr>
                                <td><span class="badge bg-light text-dark border small">{{ $line->service }}</span></td>
                                <td>{{ $line->category ?: '—' }}</td>
                                <td>{{ $line->product ?: '—' }}</td>
                                <td>{{ $line->description ?: '—' }}</td>
                                <td>{{ $line->unit ?: '—' }}</td>
                                <td class="text-end">{{ number_format($line->quantity, 2, '.', ' ') }}</td>
                                <td class="text-end">{{ number_format($line->unit_price, 0, '.', ' ') }}</td>
                                <td class="text-end">{{ number_format($line->discount_fixed_amount, 0, '.', ' ') }}</td>
                                <td class="text-end fw-semibold">{{ number_format($line->amount_before_vat, 0, '.', ' ') }}</td>
                                <td class="text-end">{{ rtrim(rtrim(number_format((float)$line->vat_rate * 100, 2), '0'), '.') }}%</td>
                                <td class="text-end">{{ number_format($line->vat_amount, 0, '.', ' ') }}</td>
                                <td class="text-end fw-semibold">{{ number_format($line->total_amount, 0, '.', ' ') }}</td>
                                <td>{{ $line->journal_entry_no ?: '—' }}</td>
                                <td><span class="badge bg-{{ $sendBadge }}">{{ $line->send_to_odoo }}</span></td>
                                <td>{{ $line->comments ?: '—' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="8" class="text-end fw-bold">{{ __('locale.acct_totals') }}</td>
                                <td class="text-end fw-bold">{{ number_format($inv->subtotal, 0, '.', ' ') }}</td>
                                <td></td>
                                <td class="text-end fw-bold">{{ number_format($inv->tax_amount, 0, '.', ' ') }}</td>
                                <td class="text-end fw-bold">{{ number_format($inv->total_amount, 0, '.', ' ') }}</td>
                                <td colspan="3"></td>
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

    {{-- Generate from Billing modal --}}
    <div class="modal fade" id="generateInvoiceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('accounting.invoices.generate') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('locale.acct_generate_from_billing') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">{{ __('locale.acct_billing_id') }}</label>
                            <input type="number" name="billing_id" class="form-control" placeholder="{{ __('locale.acct_billing_id') }}" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('locale.acct_cancel') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('locale.acct_generate_btn') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    document.querySelectorAll('.invoice-toggle').forEach(function(el) {
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
