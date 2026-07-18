<x-app-layout>
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">{{ __('locale.acct_invoice_detail') }}</h4>
                <div class="d-flex gap-2">
                    @if($invoice->status === 'draft')
                    <a href="{{ route('accounting.invoices.edit', $invoice->id) }}" class="btn btn-warning btn-sm">
                        <i class="fa fa-edit me-1"></i> {{ __('locale.acct_edit_invoice') }}
                    </a>
                    @endif
                    <a href="{{ route('accounting.invoices.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fa fa-arrow-left me-1"></i> {{ __('locale.acct_back') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body p-4">

                    {{-- Invoice Header --}}
                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <div>
                            <h3 class="text-primary fw-bold">Cool AgriStock</h3>
                            <p class="text-muted mb-0">{{ __('locale.acct_cold_storage_management') }}</p>
                        </div>
                        <div class="text-end">
                            <h2 class="fw-bold text-uppercase" style="letter-spacing:2px">{{ __('locale.acct_invoice') }}</h2>
                            <p class="mb-0"><strong>{{ $invoice->invoice_number }}</strong></p>
                            @php
                                $badges = ['draft'=>'secondary','issued'=>'primary','paid'=>'success','cancelled'=>'danger'];
                            @endphp
                            <span class="badge bg-{{ $badges[$invoice->status] ?? 'secondary' }} mt-1">{{ ucfirst($invoice->status) }}</span>
                        </div>
                    </div>

                    <hr>

                    {{-- Billing Info --}}
                    <div class="row mb-4">
                        <div class="col-sm-6">
                            <h6 class="text-muted text-uppercase mb-2" style="font-size:0.75rem; letter-spacing:1px">{{ __('locale.acct_bill_to') }}</h6>
                            <p class="mb-0 fw-semibold">{{ $invoice->customer->name ?? $invoice->customer_name ?? '—' }}</p>
                            <p class="mb-0 text-muted">{{ $invoice->customer->email ?? '' }}</p>
                            <p class="mb-0 text-muted">{{ $invoice->customer->phone ?? '' }}</p>
                        </div>
                        <div class="col-sm-6 text-sm-end">
                            <table class="ms-auto">
                                <tr>
                                    <td class="text-muted pe-3">{{ __('locale.acct_invoice_date') }}:</td>
                                    <td><strong>{{ $invoice->invoice_date->format('d/m/Y') }}</strong></td>
                                </tr>
                                @if($invoice->due_date)
                                <tr>
                                    <td class="text-muted pe-3">{{ __('locale.acct_due_date') }}:</td>
                                    <td><strong>{{ $invoice->due_date->format('d/m/Y') }}</strong></td>
                                </tr>
                                @endif
                                @if($invoice->billing)
                                <tr>
                                    <td class="text-muted pe-3">{{ __('locale.acct_billing_ref') }}:</td>
                                    <td><strong>#{{ $invoice->billing->id }}</strong></td>
                                </tr>
                                @endif
                                @if($invoice->stock_lot)
                                <tr>
                                    <td class="text-muted pe-3">{{ __('locale.acct_stock_lot') }}:</td>
                                    <td><strong>{{ $invoice->stock_lot }}</strong></td>
                                </tr>
                                @endif
                                @if($invoice->payment_terms)
                                <tr>
                                    <td class="text-muted pe-3">{{ __('locale.acct_payment_terms') }}:</td>
                                    <td><strong>{{ $invoice->payment_terms }}</strong></td>
                                </tr>
                                @endif
                            </table>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-4">
                            <span class="text-muted small d-block">{{ __('locale.acct_finance_status') }}</span>
                            <span class="badge bg-info">{{ $invoice->finance_status ?? __('locale.acct_to_review') }}</span>
                        </div>
                        <div class="col-md-4">
                            <span class="text-muted small d-block">{{ __('locale.acct_odoo_status') }}</span>
                            @if($invoice->odoo_invoice_name)
                                <span class="badge bg-success">{{ $invoice->odoo_invoice_name }}</span>
                            @elseif($invoice->odoo_push_error)
                                <span class="badge bg-danger" title="{{ $invoice->odoo_push_error }}">{{ __('locale.acct_push_failed') }}</span>
                            @else
                                <span class="badge bg-secondary">{{ __('locale.acct_not_exported') }}</span>
                            @endif
                        </div>
                        <div class="col-md-4">
                            <span class="text-muted small d-block">{{ __('locale.acct_odoo_partner_ref') }}</span>
                            <span>{{ $invoice->odoo_partner_ref ?: '—' }}</span>
                        </div>
                        @if($invoice->odoo_decision_reason)
                        <div class="col-12 mt-2">
                            <span class="text-muted small d-block">{{ __('locale.acct_odoo_decision_reason') }}</span>
                            <span>{{ $invoice->odoo_decision_reason }}</span>
                        </div>
                        @endif
                    </div>

                    {{-- Line Items --}}
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered table-sm">
                            <thead class="table-light">
                                @if($invoice->lines->isNotEmpty())
                                <tr>
                                    <th>{{ __('locale.acct_invoice_no') }}</th>
                                    <th>{{ __('locale.acct_event_type') }}</th>
                                    <th>{{ __('locale.acct_category') }}</th>
                                    <th>{{ __('locale.acct_product') }}</th>
                                    <th>{{ __('locale.acct_description') }}</th>
                                    <th>{{ __('locale.acct_unit') }}</th>
                                    <th class="text-end">{{ __('locale.acct_quantity') }}</th>
                                    <th class="text-end">{{ __('locale.acct_unit_price') }}</th>
                                    <th class="text-end">{{ __('locale.acct_discount_fixed') }}</th>
                                    <th class="text-end">{{ __('locale.acct_amount_before_vat') }}</th>
                                    <th class="text-end">{{ __('locale.acct_vat_rate') }}</th>
                                    <th class="text-end">{{ __('locale.acct_vat_amount') }}</th>
                                    <th class="text-end">{{ __('locale.acct_total_amount') }}</th>
                                    <th>{{ __('locale.acct_journal_entry_no') }}</th>
                                    <th>{{ __('locale.acct_send_to_odoo_q') }}</th>
                                    <th>{{ __('locale.acct_comments') }}</th>
                                </tr>
                                @else
                                <tr>
                                    <th>{{ __('locale.acct_description') }}</th>
                                    <th class="text-end">{{ __('locale.amount') }} (XOF)</th>
                                </tr>
                                @endif
                            </thead>
                            <tbody>
                                @if($invoice->lines->isNotEmpty())
                                    @foreach($invoice->lines as $line)
                                    <tr>
                                        <td>{{ $invoice->invoice_number }}</td>
                                        <td>{{ $line->service }}</td>
                                        <td>{{ $line->category ?: '—' }}</td>
                                        <td>{{ $line->product ?: '—' }}</td>
                                        <td>{{ $line->description ?: '—' }}</td>
                                        <td>{{ $line->unit ?: '—' }}</td>
                                        <td class="text-end">{{ number_format($line->quantity, 2, '.', ' ') }}</td>
                                        <td class="text-end">{{ number_format($line->unit_price, 0, '.', ' ') }}</td>
                                        <td class="text-end">{{ number_format($line->discount_fixed_amount, 0, '.', ' ') }}</td>
                                        <td class="text-end">{{ number_format($line->amount_before_vat, 0, '.', ' ') }}</td>
                                        <td class="text-end">{{ rtrim(rtrim(number_format((float)$line->vat_rate * 100, 2, '.', ''), '0'), '.') }}%</td>
                                        <td class="text-end">{{ number_format($line->vat_amount, 0, '.', ' ') }}</td>
                                        <td class="text-end">{{ number_format($line->total_amount, 0, '.', ' ') }}</td>
                                        <td>{{ $line->journal_entry_no ?: '—' }}</td>
                                        <td>{{ $line->send_to_odoo }}</td>
                                        <td>{{ $line->comments ?: '—' }}</td>
                                    </tr>
                                    @endforeach
                                @elseif($invoice->billing)
                                <tr>
                                    <td>
                                        <strong>{{ ucfirst(str_replace('_', ' ', $invoice->billing->type ?? 'Service')) }}</strong>
                                        @if($invoice->billing->stock)
                                        <br><small class="text-muted">Stock Ref: {{ $invoice->billing->stock->ref }}</small>
                                        @endif
                                    </td>
                                    <td class="text-end">{{ number_format($invoice->subtotal, 0, '.', ' ') }}</td>
                                </tr>
                                @else
                                <tr>
                                    <td>{{ __('locale.acct_services_rendered') }}</td>
                                    <td class="text-end">{{ number_format($invoice->subtotal, 0, '.', ' ') }}</td>
                                </tr>
                                @endif
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="{{ $invoice->lines->isNotEmpty() ? 14 : 1 }}" class="text-end text-muted">{{ __('locale.acct_subtotal') }}</td>
                                    <td class="text-end">{{ number_format($invoice->subtotal, 0, '.', ' ') }} XOF</td>
                                    @if($invoice->lines->isNotEmpty())<td colspan="2"></td>@endif
                                </tr>
                                <tr>
                                    <td colspan="{{ $invoice->lines->isNotEmpty() ? 14 : 1 }}" class="text-end text-muted">{{ __('locale.acct_tax') }}</td>
                                    <td class="text-end">{{ number_format($invoice->tax_amount, 0, '.', ' ') }} XOF</td>
                                    @if($invoice->lines->isNotEmpty())<td colspan="2"></td>@endif
                                </tr>
                                <tr class="table-primary">
                                    <td colspan="{{ $invoice->lines->isNotEmpty() ? 14 : 1 }}" class="text-end fw-bold">{{ __('locale.acct_total') }}</td>
                                    <td class="text-end fw-bold fs-5">{{ number_format($invoice->total_amount, 0, '.', ' ') }} XOF</td>
                                    @if($invoice->lines->isNotEmpty())<td colspan="2"></td>@endif
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    {{-- Notes --}}
                    @if($invoice->notes)
                    <div class="alert alert-light border">
                        <strong>{{ __('locale.acct_notes') }}:</strong> {{ $invoice->notes }}
                    </div>
                    @endif

                    {{-- Footer --}}
                    <div class="text-muted text-center mt-4" style="font-size:0.8rem">
                        <p class="mb-0">{{ __('locale.acct_generated_by') }} {{ $invoice->generatedBy->name ?? '—' }} · {{ $invoice->created_at->format('d/m/Y H:i') }}</p>
                        <p class="mb-0">© {{ date('Y') }} Cool AgriStock. {{ __('locale.acct_thank_you') }}</p>
                    </div>

                </div>
            </div>

            {{-- Journal Entries linked to this invoice --}}
            @if(isGroupAuthorized([1,2,3]))
            <div class="card mt-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">{{ __('locale.acct_linked_journal_entries') }}</h6>
                    <a href="{{ route('accounting.journal.index') }}" class="btn btn-sm btn-label-primary">{{ __('locale.acct_view_journal') }}</a>
                </div>
                <div class="card-body">
                    @php $entries = $invoice->journalEntries()->with('createdBy')->get(); @endphp
                    @if($entries->isEmpty())
                        <p class="text-muted mb-0">{{ __('locale.acct_no_journal_entries') }}</p>
                    @else
                    <table class="table table-sm">
                        <thead><tr><th>{{ __('locale.acct_reference') }}</th><th>{{ __('locale.acct_date') }}</th><th>{{ __('locale.acct_status') }}</th><th>Odoo</th><th></th></tr></thead>
                        <tbody>
                            @foreach($entries as $entry)
                            <tr>
                                <td>{{ $entry->reference }}</td>
                                <td>{{ $entry->entry_date->format('d/m/Y') }}</td>
                                <td><span class="badge bg-secondary">{{ $entry->status }}</span></td>
                                <td><span class="badge bg-warning text-dark">{{ str_replace('_',' ', $entry->odoo_status) }}</span></td>
                                <td><a href="{{ route('accounting.journal.show', $entry->id) }}" class="btn btn-xs btn-label-info"><i class="fa fa-eye"></i></a></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
