<x-app-layout>
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">{{ __('locale.acct_invoice_generator') }}</h4>
                <a href="{{ route('accounting.invoices.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fa fa-arrow-left me-1"></i> {{ __('locale.acct_back') }}
                </a>
            </div>
        </div>
    </div>

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <form action="{{ route('accounting.invoices.store') }}" method="POST" id="invoiceForm" novalidate>
        <input type="hidden" name="finance_status" value="To review">
        <input type="hidden" name="send_to_odoo" value="No">
        <input type="hidden" name="odoo_decision_reason" value="">
        @csrf

        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">COOL AGRISTOCK - {{ __('locale.acct_invoice_generator') }}</h6>
                <small class="text-muted">{{ __('locale.acct_invoice_generator_desc') }}</small>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">{{ __('locale.acct_invoice_date') }} <span class="text-danger">*</span></label>
                        <input type="date" name="invoice_date" class="form-control" value="{{ old('invoice_date', now()->toDateString()) }}" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">{{ __('locale.acct_currency') }} <span class="text-danger">*</span></label>
                        <input type="text" name="currency" class="form-control text-uppercase" maxlength="3" value="{{ old('currency', 'XOF') }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('locale.acct_customer') }} <span class="text-danger">*</span></label>
                        <select class="form-select" id="customerSelect"
                            onchange="
                                var inp = document.getElementById('customerNameInput');
                                var hid = document.getElementById('customerIdHidden');
                                if (this.value === 'other') {
                                    inp.style.display = 'block';
                                    inp.focus();
                                    hid.value = '';
                                } else {
                                    inp.style.display = 'none';
                                    inp.value = '';
                                    hid.value = this.value;
                                }
                            ">
                            <option value="">{{ __('locale.acct_select_customer') }}</option>
                            @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" {{ (string) old('customer_id') === (string) $customer->id ? 'selected' : '' }}>
                                {{ $customer->name }}@if($customer->email) ({{ $customer->email }})@endif
                            </option>
                            @endforeach
                            <option value="other" {{ old('customer_name') ? 'selected' : '' }}>{{ __('locale.acct_other_type_name') }}</option>
                        </select>
                        <input type="hidden" name="customer_id" id="customerIdHidden" value="{{ old('customer_id') }}">
                        <input type="text" name="customer_name" id="customerNameInput"
                            class="form-control mt-1" placeholder="{{ __('locale.acct_customer') }}..."
                            value="{{ old('customer_name') }}"
                            style="{{ old('customer_name') ? '' : 'display:none' }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('locale.acct_due_date') }}</label>
                        <input type="date" name="due_date" class="form-control" value="{{ old('due_date') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('locale.acct_stock_lot') }}</label>
                        <input type="text" name="stock_lot" class="form-control" value="{{ old('stock_lot') }}" placeholder="e.g. LOT-2026-001">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('locale.acct_payment_terms') }}</label>
                        <input type="text" name="payment_terms" class="form-control" value="{{ old('payment_terms', 'Due on receipt') }}" placeholder="e.g. Due on receipt, Net 30">
                    </div>
                    <input type="hidden" name="odoo_partner_ref" value="">
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">{{ __('locale.acct_invoice_lines') }}</h6>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-label-secondary" id="loadSamples">
                        <i class="fa fa-table me-1"></i> {{ __('locale.acct_load_samples') }}
                    </button>
                    <button type="button" class="btn btn-sm btn-primary" id="addLine">
                        <i class="fa fa-plus me-1"></i> {{ __('locale.acct_add_line') }}
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="min-width:150px">{{ __('locale.acct_invoice_no') }}</th>
                                <th style="min-width:180px">{{ __('locale.acct_event_type') }}</th>
                                <th style="min-width:160px">{{ __('locale.acct_category') }}</th>
                                <th style="min-width:160px">{{ __('locale.acct_product') }}</th>
                                <th style="min-width:240px">{{ __('locale.acct_description') }}</th>
                                <th style="min-width:120px">{{ __('locale.acct_unit') }}</th>
                                <th style="min-width:120px" class="text-end">{{ __('locale.acct_quantity') }}</th>
                                <th style="min-width:130px" class="text-end">{{ __('locale.acct_unit_price') }}</th>
                                <th style="min-width:150px" class="text-end">{{ __('locale.acct_discount_fixed') }}</th>
                                <th style="min-width:150px" class="text-end">{{ __('locale.acct_amount_before_vat') }}</th>
                                <th style="min-width:110px" class="text-end">{{ __('locale.acct_vat_rate') }}</th>
                                <th style="min-width:130px" class="text-end">{{ __('locale.acct_vat_amount') }}</th>
                                <th style="min-width:160px" class="text-end">{{ __('locale.acct_total_amount') }}</th>
                                <th style="min-width:180px">{{ __('locale.acct_journal_entry_no') }} <span class="text-muted fw-normal small">({{ __('locale.acct_auto') }})</span></th>
                                <th style="min-width:150px">{{ __('locale.acct_send_to_odoo_q') }}</th>
                                <th style="min-width:220px">{{ __('locale.acct_comments') }}</th>
                                <th style="min-width:70px"></th>
                            </tr>
                        </thead>
                        <tbody id="invoiceLines"></tbody>
                        <tfoot>
                            <tr>
                                <td colspan="9" class="text-end fw-bold">{{ __('locale.acct_totals') }}</td>
                                <td class="text-end fw-bold" id="subtotalCell">0</td>
                                <td></td>
                                <td class="text-end fw-bold" id="taxCell">0</td>
                                <td class="text-end fw-bold" id="totalCell">0</td>
                                <td colspan="4"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <input type="hidden" name="_action" id="formAction" value="generate">

        <div class="card">
            <div class="card-body d-flex justify-content-end gap-2">
                <button type="submit" class="btn btn-primary px-4"
                    onclick="document.getElementById('formAction').value='generate'">
                    <i class="fa fa-save me-1"></i> {{ __('locale.acct_generate_invoice') }}
                </button>
                <button type="submit" class="btn btn-success px-4"
                    onclick="document.getElementById('formAction').value='send_odoo'">
                    <i class="fa fa-paper-plane me-1"></i> {{ __('locale.acct_send_to_odoo') }}
                </button>
            </div>
        </div>
    </form>

    @push('scripts')
    <script>
    (function () {
        let lineIndex = 0;
        const serviceOptions = @json($eventTypeOptions);
        const categoryOptions = @json($categoryOptions);
        const productOptions = @json($productOptions);
        const unitOptions = @json($unitOptions);
        const sendOptions = @json($sendToOdooOptions);
        const samples = @json($sampleRows);

        function escapeHtml(value) {
            return String(value ?? '').replace(/[&<>"']/g, char => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
            }[char]));
        }

        function optionsHtml(options, selected = '') {
            return ['<option value=""></option>'].concat(options.map(option => {
                const isSelected = option === selected ? ' selected' : '';
                return `<option value="${escapeHtml(option)}"${isSelected}>${escapeHtml(option)}</option>`;
            })).join('');
        }

        function rowTemplate(idx, row = {}) {
            const rowService = row.service || '';
            const isOtherService = rowService !== '' && !serviceOptions.includes(rowService);
            const serviceSelectValue = isOtherService ? 'Other' : rowService;
            const otherServiceValue  = isOtherService ? rowService : '';
            return `<tr class="invoice-line">
                <td class="text-muted small">{{ __('locale.acct_auto') }}</td>
                <td>
                    <select class="form-select form-select-sm service-select">${optionsHtml(serviceOptions, serviceSelectValue)}</select>
                    <input type="hidden" name="lines[${idx}][service]" class="service-hidden" value="${escapeHtml(rowService)}">
                    <input type="text" class="form-control form-control-sm service-other-input mt-1" placeholder="{{ __('locale.acct_event_type') }}..." ${isOtherService ? '' : 'style="display:none"'} value="${escapeHtml(otherServiceValue)}">
                </td>
                <td><select name="lines[${idx}][category]" class="form-select form-select-sm">${optionsHtml(categoryOptions, row.category || '')}</select></td>
                <td><select name="lines[${idx}][product]" class="form-select form-select-sm">${optionsHtml(productOptions, row.product || '')}</select></td>
                <td><input type="text" name="lines[${idx}][description]" class="form-control form-control-sm" value="${escapeHtml(row.description || '')}"></td>
                <td><select name="lines[${idx}][unit]" class="form-select form-select-sm">${optionsHtml(unitOptions, row.unit || '')}</select></td>
                <td><input type="number" name="lines[${idx}][quantity]" class="form-control form-control-sm text-end calc-input quantity" min="0" step="0.0001" value="${escapeHtml(row.quantity ?? 0)}" required></td>
                <td><input type="number" name="lines[${idx}][unit_price]" class="form-control form-control-sm text-end calc-input unit-price" min="0" step="1" value="${escapeHtml(row.unit_price ?? 0)}" required></td>
                <td><input type="number" name="lines[${idx}][discount_fixed_amount]" class="form-control form-control-sm text-end calc-input discount" min="0" step="1" value="${escapeHtml(row.discount_fixed_amount ?? 0)}"></td>
                <td class="text-end amount-before-vat">0</td>
                <td><input type="number" name="lines[${idx}][vat_rate]" class="form-control form-control-sm text-end calc-input vat-rate" min="0" step="0.01" value="${escapeHtml(row.vat_rate ?? 0)}"></td>
                <td class="text-end vat-amount">0</td>
                <td class="text-end line-total">0</td>
                <td><input type="text" name="lines[${idx}][journal_entry_no]" class="form-control form-control-sm" value="${escapeHtml(row.journal_entry_no || '')}" placeholder="Optional"></td>
                <td><select name="lines[${idx}][send_to_odoo]" class="form-select form-select-sm" required>${optionsHtml(sendOptions, row.send_to_odoo || 'No')}</select></td>
                <td><input type="text" name="lines[${idx}][comments]" class="form-control form-control-sm" value="${escapeHtml(row.comments || '')}"></td>
                <td style="min-width:70px;white-space:nowrap" class="text-center">
                    <button type="button" class="btn btn-xs btn-success generate-line-btn" title="{{ __('locale.acct_generate_invoice') }}" style="margin-right:4px"><i class="fa fa-file-invoice"></i></button><button type="button" class="btn btn-xs btn-danger remove-line"><i class="fa fa-trash"></i></button>
                </td>
            </tr>`;
        }

        function addRow(row = {}) {
            document.getElementById('invoiceLines').insertAdjacentHTML('beforeend', rowTemplate(lineIndex++, row));
            bindEvents();
            recalculate();
            updateRemoveButtons();
        }

        function recalculate() {
            let subtotal = 0, tax = 0, total = 0;
            document.querySelectorAll('.invoice-line').forEach((row) => {
                const quantity = parseFloat(row.querySelector('.quantity').value) || 0;
                const unitPrice = parseFloat(row.querySelector('.unit-price').value) || 0;
                const discount = parseFloat(row.querySelector('.discount').value) || 0;
                const vatRate = parseFloat(row.querySelector('.vat-rate').value) || 0;
                const amountBeforeVat = Math.max((quantity * unitPrice) - discount, 0);
                const vatAmount = amountBeforeVat * vatRate;
                const lineTotal = amountBeforeVat + vatAmount;
                row.querySelector('.amount-before-vat').textContent = amountBeforeVat.toLocaleString('fr-FR');
                row.querySelector('.vat-amount').textContent = vatAmount.toLocaleString('fr-FR');
                row.querySelector('.line-total').textContent = lineTotal.toLocaleString('fr-FR');
                subtotal += amountBeforeVat;
                tax += vatAmount;
                total += lineTotal;
            });
            document.getElementById('subtotalCell').textContent = subtotal.toLocaleString('fr-FR');
            document.getElementById('taxCell').textContent = tax.toLocaleString('fr-FR');
            document.getElementById('totalCell').textContent = total.toLocaleString('fr-FR');
        }

        function handleServiceChange(e) {
            const sel = e.target;
            const td = sel.closest('td');
            const otherInput = td.querySelector('.service-other-input');
            const hiddenInput = td.querySelector('.service-hidden');
            if (sel.value === 'Other') {
                otherInput.style.display = '';
                otherInput.focus();
                hiddenInput.value = otherInput.value.trim() || '';
            } else {
                otherInput.style.display = 'none';
                otherInput.value = '';
                otherInput.classList.remove('is-invalid');
                hiddenInput.value = sel.value;
            }
        }

        function handleServiceOtherInput(e) {
            const input = e.target;
            input.closest('td').querySelector('.service-hidden').value = input.value.trim();
        }

        function generateLineRow(btn) {
            const tr = btn.closest('tr');
            const rowData = {};
            tr.querySelectorAll('input[name], select[name]').forEach(el => {
                const m = el.name.match(/lines\[\d+\]\[(.+)\]/);
                if (m) rowData[m[1]] = el.value;
            });
            const sOther = tr.querySelector('.service-other-input');
            if (sOther && sOther.style.display !== 'none') rowData.service = sOther.value;
            // Also pass header fields
            rowData.customer_id   = document.getElementById('customerIdHidden')?.value || '';
            rowData.customer_name = document.getElementById('customerNameInput')?.value || '';
            rowData.invoice_date  = document.querySelector('input[name="invoice_date"]')?.value || '';
            rowData.currency      = document.querySelector('input[name="currency"]')?.value || 'XOF';

            btn.disabled = true;
            btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';

            const csrf = document.querySelector('meta[name="csrf-token"]')?.content
                      || document.querySelector('input[name="_token"]')?.value;

            fetch('{{ route("accounting.invoices.process-line") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ line: rowData }),
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    btn.innerHTML = '<i class="fa fa-check"></i>';
                    btn.classList.replace('btn-success', 'btn-secondary');
                    window.open(data.pdf_url, '_blank');
                } else {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa fa-file-invoice"></i>';
                    alert('Failed: ' + data.message);
                }
            })
            .catch(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa fa-file-invoice"></i>';
                alert('Network error — try again.');
            });
        }

        function bindEvents() {
            document.querySelectorAll('.calc-input').forEach(el => {
                el.removeEventListener('input', recalculate);
                el.addEventListener('input', recalculate);
            });
            document.querySelectorAll('.remove-line').forEach(button => {
                button.removeEventListener('click', removeRow);
                button.addEventListener('click', removeRow);
            });
            document.querySelectorAll('.generate-line-btn').forEach(btn => {
                btn.onclick = function () { generateLineRow(this); };
            });
            document.querySelectorAll('.service-select').forEach(sel => {
                sel.removeEventListener('change', handleServiceChange);
                sel.addEventListener('change', handleServiceChange);
            });
            document.querySelectorAll('.service-other-input').forEach(input => {
                input.removeEventListener('input', handleServiceOtherInput);
                input.addEventListener('input', handleServiceOtherInput);
            });
        }

        function removeRow(event) {
            event.target.closest('tr').remove();
            recalculate();
            updateRemoveButtons();
        }

        function updateRemoveButtons() {
            const rows = document.querySelectorAll('.invoice-line');
            rows.forEach(row => {
                const button = row.querySelector('.remove-line');
                if (button) button.disabled = rows.length <= 1;
            });
        }

        document.getElementById('invoiceForm').addEventListener('submit', function (e) {
            const sel = document.getElementById('customerSelect');
            const nameInp = document.getElementById('customerNameInput');
            if (!sel.value) {
                sel.classList.add('is-invalid');
                e.preventDefault();
                return;
            }
            sel.classList.remove('is-invalid');
            if (sel.value === 'other' && !nameInp.value.trim()) {
                nameInp.classList.add('is-invalid');
                e.preventDefault();
                return;
            }
            nameInp.classList.remove('is-invalid');
            let valid = true;
            document.querySelectorAll('.service-select').forEach(sel => {
                if (sel.value === 'Other') {
                    const td = sel.closest('td');
                    const otherInput = td.querySelector('.service-other-input');
                    if (!otherInput.value.trim()) {
                        otherInput.classList.add('is-invalid');
                        valid = false;
                    } else {
                        otherInput.classList.remove('is-invalid');
                        td.querySelector('.service-hidden').value = otherInput.value.trim();
                    }
                }
            });
            if (!valid) e.preventDefault();
        });

        document.getElementById('addLine').addEventListener('click', () => addRow());
        document.getElementById('loadSamples').addEventListener('click', () => {
            document.getElementById('invoiceLines').innerHTML = '';
            lineIndex = 0;
            samples.forEach(row => addRow(row));
        });
        addRow();
    })();
    </script>
    @endpush
</x-app-layout>
