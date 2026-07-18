<x-app-layout>
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">{{ isset($entry) ? __('locale.acct_edit_journal_entry') : __('locale.acct_new_journal_entry') }}</h4>
                <a href="{{ route('accounting.journal.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fa fa-arrow-left me-1"></i> {{ __('locale.acct_back') }}
                </a>
            </div>
        </div>
    </div>

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <form action="{{ isset($entry) ? route('accounting.journal.update', $entry->id) : route('accounting.journal.process') }}" method="POST" id="journalForm">
        @csrf
        @if(isset($entry)) @method('PUT') @endif
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-0">{{ __('locale.acct_general_journal') }}</h6>
                    <small class="text-muted">{{ __('locale.acct_general_journal_hint') }}</small>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-label-secondary" id="loadSamples">
                        <i class="fa fa-table me-1"></i> {{ __('locale.acct_load_samples') }}
                    </button>
                    <button type="button" class="btn btn-sm btn-primary" id="addLine">
                        <i class="fa fa-plus me-1"></i> {{ __('locale.acct_add_row') }}
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0 align-middle" id="linesTable">
                        <thead class="table-light">
                            <tr>
                                <th style="min-width:140px">{{ __('locale.acct_date') }}</th>
                                <th style="min-width:190px">{{ __('locale.acct_journal_col') }}</th>
                                <th style="min-width:190px">{{ __('locale.acct_reference') }} <span class="text-muted fw-normal small">(optional)</span></th>
                                <th style="min-width:200px">{{ __('locale.acct_customer_partner') }}</th>
                                <th style="min-width:210px">{{ __('locale.acct_event_type') }}</th>
                                <th style="min-width:190px">{{ __('locale.acct_provenance_category') }}</th>
                                <th style="min-width:260px">{{ __('locale.acct_description') }}</th>
                                <th style="min-width:120px" class="text-end">{{ __('locale.acct_debit') }}</th>
                                <th style="min-width:120px" class="text-end">{{ __('locale.acct_credit') }}</th>
                                <th style="min-width:90px">{{ __('locale.acct_currency') }}</th>
                                <th style="min-width:170px">{{ __('locale.acct_send_to_odoo_q') }}</th>
                                <th style="min-width:150px">{{ __('locale.acct_odoo_status') }}</th>
                                <th style="min-width:240px">{{ __('locale.acct_comments') }}</th>
                                <th style="width:48px"></th>
                            </tr>
                        </thead>
                        <tbody id="linesBody"></tbody>
                        <tfoot>
                            <tr>
                                <td colspan="7" class="text-end fw-bold">{{ __('locale.acct_totals') }}</td>
                                <td class="text-end fw-bold" id="totalDebit">0</td>
                                <td class="text-end fw-bold" id="totalCredit">0</td>
                                <td colspan="5"></td>
                            </tr>
                            <tr id="balanceRow">
                                <td colspan="13" class="text-center fw-bold" id="balanceIndicator">
                                    {{ __('locale.acct_balance_check_hint') }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body d-flex flex-wrap justify-content-between gap-3">
                <p class="text-muted small mb-0">
                    {{ __('locale.acct_process_hint') }}
                </p>
                <button type="submit" class="btn btn-success px-4" id="submitBtn">
                    <i class="fa fa-paper-plane me-1"></i> {{ isset($entry) ? __('locale.acct_save_changes') : __('locale.acct_process') }}
                </button>
            </div>
        </div>
    </form>

    @push('scripts')
    @php
        $existingJournalLines = isset($entry)
            ? $entry->lines->map(fn($l) => [
                'line_date' => optional($l->line_date)->toDateString(),
                'journal' => $l->journal,
                'document_reference' => $l->document_reference,
                'customer_id' => $l->customer_id,
                'customer_name' => $l->customer_name,
                'event_type' => $l->event_type,
                'provenance_category' => $l->provenance_category,
                'description' => $l->description,
                'debit' => $l->debit,
                'credit' => $l->credit,
                'currency' => $l->currency,
                'send_to_odoo' => $l->send_to_odoo,
                'comments' => $l->comments,
            ])->values()
            : collect();
    @endphp
    <script>
    (function () {
        let lineIndex = 0;
        const journalOptions = @json($journalOptions);
        const eventTypes = @json($eventTypes);
        const provenanceOptions = @json($provenanceOptions);
        const sendOptions = @json($sendToOdooOptions);
        const odooStatuses = @json($odooStatusOptions);
        const samples = @json($sampleRows);
        const knownEventTypes  = @json($eventTypes);
        const knownJournals    = @json($journalOptions);
        const customerOptions  = @json($customers->map(fn($c) => ['id' => $c->id, 'name' => $c->name])->values());
        const labelNotExported = @json(__('locale.acct_not_exported'));
        const labelBalanceHint = @json(__('locale.acct_balance_check_hint'));
        const labelBalanced    = @json(__('locale.acct_balanced'));
        const labelUnbalanced  = @json(__('locale.acct_unbalanced'));
        const isEditing = @json(isset($entry));
        const existingLines = @json($existingJournalLines);

        function optionsHtml(options, selected = '') {
            return ['<option value=""></option>'].concat(options.map(option => {
                const isSelected = option === selected ? ' selected' : '';
                return `<option value="${escapeHtml(option)}"${isSelected}>${escapeHtml(option)}</option>`;
            })).join('');
        }

        function customerOptionsHtml(selectedId = '') {
            let html = '<option value="">— Select —</option>';
            customerOptions.forEach(c => {
                const sel = String(c.id) === String(selectedId) ? ' selected' : '';
                html += `<option value="${escapeHtml(String(c.id))}"${sel}>${escapeHtml(c.name)}</option>`;
            });
            html += `<option value="other"${selectedId === 'other' ? ' selected' : ''}>Other (type name)</option>`;
            return html;
        }

        function escapeHtml(value) {
            return String(value ?? '').replace(/[&<>"']/g, char => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
            }[char]));
        }

        function rowTemplate(idx, row = {}) {
            const provCat = row.provenance_category || '';
            const isAgent = provCat.startsWith('COOL AGRISTOCK agent(') && provCat !== 'COOL AGRISTOCK agent(Name of the person)';
            const agentName = isAgent ? provCat.replace(/^COOL AGRISTOCK agent\((.+)\)$/, '$1') : '';
            const provenanceSelectValue = isAgent ? 'COOL AGRISTOCK agent(Name of the person)' : provCat;
            const rowJournal = row.journal || '';
            const isOtherJournal = rowJournal !== '' && !knownJournals.includes(rowJournal);
            const journalSelectValue = isOtherJournal ? 'Other' : rowJournal;
            const otherJournalValue  = isOtherJournal ? rowJournal : '';
            const rowCustomerId   = row.customer_id || '';
            const rowCustomerName = row.customer_name || '';
            const isOtherCustomer = rowCustomerId === 'other' || (!rowCustomerId && rowCustomerName);
            const customerSelectVal = isOtherCustomer ? 'other' : rowCustomerId;
            const rowEventType = row.event_type || '';
            const isOtherEvent = rowEventType !== '' && !knownEventTypes.includes(rowEventType);
            const eventSelectValue = isOtherEvent ? 'Other' : rowEventType;
            const otherEventValue = isOtherEvent ? rowEventType : '';
            return `<tr class="line-row">
                <td><input type="date" name="lines[${idx}][line_date]" class="form-control form-control-sm" value="${escapeHtml(row.line_date || new Date().toISOString().slice(0, 10))}" required></td>
                <td>
                    <select class="form-select form-select-sm journal-select">${optionsHtml(journalOptions, journalSelectValue)}</select>
                    <input type="hidden" name="lines[${idx}][journal]" class="journal-hidden" value="${escapeHtml(rowJournal)}">
                    <input type="text" class="form-control form-control-sm journal-other-input mt-1" placeholder="Specify journal..." ${isOtherJournal ? '' : 'style="display:none"'} value="${escapeHtml(otherJournalValue)}">
                </td>
                <td><input type="text" name="lines[${idx}][document_reference]" class="form-control form-control-sm" value="${escapeHtml(row.document_reference || '')}"></td>
                <td>
                    <select class="form-select form-select-sm customer-select"
                        onchange="(function(sel){
                            var td=sel.closest('td');
                            var inp=td.querySelector('.customer-other-input');
                            var hid=td.querySelector('.customer-id-hidden');
                            var namHid=td.querySelector('.customer-name-hidden');
                            if(sel.value==='other'){inp.style.display='block';inp.focus();hid.value='';namHid.value=inp.value;}
                            else{inp.style.display='none';inp.value='';hid.value=sel.value;namHid.value='';}
                        })(this)">
                        ${customerOptionsHtml(customerSelectVal)}
                    </select>
                    <input type="hidden" name="lines[${idx}][customer_id]" class="customer-id-hidden" value="${escapeHtml(isOtherCustomer ? '' : rowCustomerId)}">
                    <input type="hidden" name="lines[${idx}][customer_name]" class="customer-name-hidden" value="${escapeHtml(rowCustomerName)}">
                    <input type="text" class="form-control form-control-sm customer-other-input mt-1" placeholder="Type customer name..."
                        oninput="this.closest('td').querySelector('.customer-name-hidden').value=this.value"
                        style="${isOtherCustomer ? '' : 'display:none'}" value="${escapeHtml(rowCustomerName)}">
                </td>
                <td>
                    <select class="form-select form-select-sm event-type-select">${optionsHtml(eventTypes, eventSelectValue)}</select>
                    <input type="hidden" name="lines[${idx}][event_type]" class="event-type-hidden" value="${escapeHtml(rowEventType)}">
                    <input type="text" class="form-control form-control-sm event-type-other-input mt-1" placeholder="Describe event type..." ${isOtherEvent ? '' : 'style="display:none"'} value="${escapeHtml(otherEventValue)}">
                </td>
                <td>
                    <select class="form-select form-select-sm provenance-select">${optionsHtml(provenanceOptions, provenanceSelectValue)}</select>
                    <input type="hidden" name="lines[${idx}][provenance_category]" class="provenance-hidden" value="${escapeHtml(provCat)}">
                    <input type="text" class="form-control form-control-sm agent-name-input mt-1" placeholder="Agent name..." ${isAgent ? '' : 'style="display:none"'} value="${escapeHtml(agentName)}">
                </td>
                <td><input type="text" name="lines[${idx}][description]" class="form-control form-control-sm" value="${escapeHtml(row.description || '')}" required></td>
                <td><input type="number" name="lines[${idx}][debit]" class="form-control form-control-sm text-end amount-input debit-input" value="${escapeHtml(row.debit ?? 0)}" min="0" step="1" required></td>
                <td><input type="number" name="lines[${idx}][credit]" class="form-control form-control-sm text-end amount-input credit-input" value="${escapeHtml(row.credit ?? 0)}" min="0" step="1" required></td>
                <td><input type="text" name="lines[${idx}][currency]" class="form-control form-control-sm text-uppercase" value="${escapeHtml(row.currency || 'XOF')}" maxlength="3" required></td>
                <td><select name="lines[${idx}][send_to_odoo]" class="form-select form-select-sm send-to-odoo-select" required>${optionsHtml(sendOptions, row.send_to_odoo || '')}</select></td>
                <td><input type="text" name="lines[${idx}][odoo_status]" class="form-control form-control-sm odoo-status-auto bg-light text-muted" readonly value="${escapeHtml(labelNotExported)}"></td>
                <td><input type="text" name="lines[${idx}][comments]" class="form-control form-control-sm" value="${escapeHtml(row.comments || '')}"></td>
                <td class="text-center" style="min-width:90px">
                    ${isEditing ? '' : '<button type="button" class="btn btn-xs btn-success process-line-btn me-1" title="Process this row"><i class="fa fa-paper-plane"></i></button>'}
                    <button type="button" class="btn btn-xs btn-danger remove-line"><i class="fa fa-trash"></i></button>
                </td>
            </tr>`;
        }

        function addRow(row = {}) {
            document.getElementById('linesBody').insertAdjacentHTML('beforeend', rowTemplate(lineIndex++, row));
            bindEvents();
            recalculate();
            updateRemoveButtons();
        }

        function recalculate() {
            let totalDebit = 0, totalCredit = 0;
            document.querySelectorAll('.debit-input').forEach(el => totalDebit += parseFloat(el.value) || 0);
            document.querySelectorAll('.credit-input').forEach(el => totalCredit += parseFloat(el.value) || 0);
            document.getElementById('totalDebit').textContent = totalDebit.toLocaleString('fr-FR');
            document.getElementById('totalCredit').textContent = totalCredit.toLocaleString('fr-FR');

            const row = document.getElementById('balanceRow');
            const indicator = document.getElementById('balanceIndicator');
            if (!totalDebit && !totalCredit) {
                row.className = '';
                indicator.textContent = labelBalanceHint;
            } else if (Math.abs(totalDebit - totalCredit) < 0.01) {
                row.className = 'table-success';
                indicator.innerHTML = '<i class="fa fa-check-circle me-1"></i> ' + labelBalanced;
            } else {
                row.className = 'table-danger';
                indicator.innerHTML = '<i class="fa fa-exclamation-triangle me-1"></i> ' + labelUnbalanced + ' — difference: ' +
                    Math.abs(totalDebit - totalCredit).toLocaleString('fr-FR') + ' XOF';
            }
        }

        function processLineRow(btn) {
            const tr = btn.closest('tr');
            const rowData = {};
            tr.querySelectorAll('input[name], select[name]').forEach(el => {
                const m = el.name.match(/lines\[\d+\]\[(.+)\]/);
                if (m) rowData[m[1]] = el.value;
            });
            // Capture "Other" inputs
            const jOther = tr.querySelector('.journal-other-input');
            if (jOther && jOther.style.display !== 'none') rowData.journal = jOther.value;
            const eOther = tr.querySelector('.event-type-other-input');
            if (eOther && eOther.style.display !== 'none') rowData.event_type = eOther.value;
            const aInput = tr.querySelector('.agent-name-input');
            if (aInput && aInput.style.display !== 'none') rowData.provenance_category = `COOL AGRISTOCK agent(${aInput.value})`;

            btn.disabled = true;
            btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
                           || document.querySelector('input[name="_token"]')?.value;

            fetch('{{ route("accounting.journal.process-line") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ line: rowData }),
            })
            .then(r => r.json())
            .then(data => {
                const statusInput = tr.querySelector('.odoo-status-auto');
                if (data.success) {
                    if (statusInput) statusInput.value = data.odoo_status;
                    btn.innerHTML = '<i class="fa fa-check"></i>';
                    btn.classList.replace('btn-success', 'btn-secondary');
                    btn.title = `Processed as ${data.reference}`;
                    tr.classList.add('table-success');
                } else {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa fa-paper-plane"></i>';
                    alert('Process failed: ' + data.message);
                }
            })
            .catch(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa fa-paper-plane"></i>';
                alert('Network error — please try again.');
            });
        }

        function handleJournalChange(e) {
            const sel = e.target;
            const td = sel.closest('td');
            const otherInput = td.querySelector('.journal-other-input');
            const hiddenInput = td.querySelector('.journal-hidden');
            if (sel.value === 'Other') {
                otherInput.style.display = '';
                hiddenInput.value = otherInput.value.trim() || '';
            } else {
                otherInput.style.display = 'none';
                otherInput.classList.remove('is-invalid');
                hiddenInput.value = sel.value;
            }
        }

        function handleJournalOtherInput(e) {
            const input = e.target;
            const td = input.closest('td');
            td.querySelector('.journal-hidden').value = input.value.trim();
        }

        function handleEventTypeChange(e) {
            const sel = e.target;
            const td = sel.closest('td');
            const otherInput = td.querySelector('.event-type-other-input');
            const hiddenInput = td.querySelector('.event-type-hidden');
            if (sel.value === 'Other') {
                otherInput.style.display = '';
                hiddenInput.value = otherInput.value.trim() || '';
            } else {
                otherInput.style.display = 'none';
                otherInput.classList.remove('is-invalid');
                hiddenInput.value = sel.value;
            }
        }

        function handleEventTypeOtherInput(e) {
            const input = e.target;
            const td = input.closest('td');
            td.querySelector('.event-type-hidden').value = input.value.trim();
        }

        function handleSendToOdooChange(e) {
            // odoo_status is only updated by the backend after actual push — nothing to do here
        }

        function handleProvenanceChange(e) {
            const sel = e.target;
            const td = sel.closest('td');
            const agentInput = td.querySelector('.agent-name-input');
            const hiddenInput = td.querySelector('.provenance-hidden');
            if (sel.value === 'COOL AGRISTOCK agent(Name of the person)') {
                agentInput.style.display = '';
                hiddenInput.value = agentInput.value ? `COOL AGRISTOCK agent(${agentInput.value})` : '';
            } else {
                agentInput.style.display = 'none';
                agentInput.classList.remove('is-invalid');
                hiddenInput.value = sel.value;
            }
        }

        function handleAgentNameInput(e) {
            const input = e.target;
            const td = input.closest('td');
            td.querySelector('.provenance-hidden').value = input.value ? `COOL AGRISTOCK agent(${input.value})` : '';
        }

        function bindEvents() {
            document.querySelectorAll('.amount-input').forEach(el => {
                el.removeEventListener('input', recalculate);
                el.addEventListener('input', recalculate);
            });
            document.querySelectorAll('.remove-line').forEach(btn => {
                btn.removeEventListener('click', removeRow);
                btn.addEventListener('click', removeRow);
            });
            document.querySelectorAll('.process-line-btn').forEach(btn => {
                btn.onclick = function () { processLineRow(this); };
            });
            document.querySelectorAll('.journal-select').forEach(sel => {
                sel.removeEventListener('change', handleJournalChange);
                sel.addEventListener('change', handleJournalChange);
            });
            document.querySelectorAll('.journal-other-input').forEach(input => {
                input.removeEventListener('input', handleJournalOtherInput);
                input.addEventListener('input', handleJournalOtherInput);
            });
            document.querySelectorAll('.event-type-select').forEach(sel => {
                sel.removeEventListener('change', handleEventTypeChange);
                sel.addEventListener('change', handleEventTypeChange);
            });
            document.querySelectorAll('.event-type-other-input').forEach(input => {
                input.removeEventListener('input', handleEventTypeOtherInput);
                input.addEventListener('input', handleEventTypeOtherInput);
            });
            document.querySelectorAll('.send-to-odoo-select').forEach(sel => {
                sel.removeEventListener('change', handleSendToOdooChange);
                sel.addEventListener('change', handleSendToOdooChange);
            });
            document.querySelectorAll('.provenance-select').forEach(sel => {
                sel.removeEventListener('change', handleProvenanceChange);
                sel.addEventListener('change', handleProvenanceChange);
            });
            document.querySelectorAll('.agent-name-input').forEach(input => {
                input.removeEventListener('input', handleAgentNameInput);
                input.addEventListener('input', handleAgentNameInput);
            });
        }

        function removeRow(event) {
            event.target.closest('tr').remove();
            recalculate();
            updateRemoveButtons();
        }

        function updateRemoveButtons() {
            const rows = document.querySelectorAll('.line-row');
            rows.forEach(row => {
                const button = row.querySelector('.remove-line');
                if (button) button.disabled = rows.length <= 1;
            });
        }

        document.getElementById('journalForm').addEventListener('submit', function (e) {
            let valid = true;

            document.querySelectorAll('.event-type-select').forEach(sel => {
                if (sel.value === 'Other') {
                    const td = sel.closest('td');
                    const otherInput = td.querySelector('.event-type-other-input');
                    if (!otherInput.value.trim()) {
                        otherInput.classList.add('is-invalid');
                        valid = false;
                    } else {
                        otherInput.classList.remove('is-invalid');
                        td.querySelector('.event-type-hidden').value = otherInput.value.trim();
                    }
                }
            });

            document.querySelectorAll('.provenance-select').forEach(sel => {
                if (sel.value === 'COOL AGRISTOCK agent(Name of the person)') {
                    const td = sel.closest('td');
                    const agentInput = td.querySelector('.agent-name-input');
                    if (!agentInput.value.trim()) {
                        agentInput.classList.add('is-invalid');
                        valid = false;
                    } else {
                        agentInput.classList.remove('is-invalid');
                        td.querySelector('.provenance-hidden').value = `COOL AGRISTOCK agent(${agentInput.value.trim()})`;
                    }
                }
            });
            if (!valid) e.preventDefault();
        });

        // ── LocalStorage auto-save ────────────────────────────────────────────
        const LS_KEY = 'cag_journal_draft';

        function collectRows() {
            const rows = [];
            document.querySelectorAll('#linesBody .line-row').forEach(tr => {
                const row = {};
                tr.querySelectorAll('input[name], select[name]').forEach(el => {
                    const m = el.name.match(/lines\[\d+\]\[(.+)\]/);
                    if (m) row[m[1]] = el.value;
                });
                // also capture visible "other" text inputs
                const journalOther = tr.querySelector('.journal-other-input');
                if (journalOther && journalOther.style.display !== 'none') row.journal = journalOther.value;
                const eventOther = tr.querySelector('.event-type-other-input');
                if (eventOther && eventOther.style.display !== 'none') row.event_type = eventOther.value;
                const agentInput = tr.querySelector('.agent-name-input');
                if (agentInput && agentInput.style.display !== 'none') row.provenance_category = `COOL AGRISTOCK agent(${agentInput.value})`;
                rows.push(row);
            });
            return rows;
        }

        function saveToLocalStorage() {
            try { localStorage.setItem(LS_KEY, JSON.stringify(collectRows())); } catch(e) {}
        }

        function restoreFromLocalStorage() {
            try {
                const saved = localStorage.getItem(LS_KEY);
                if (!saved) return;
                const rows = JSON.parse(saved);
                if (!Array.isArray(rows) || rows.length === 0) return;
                document.getElementById('linesBody').innerHTML = '';
                lineIndex = 0;
                rows.forEach(row => addRow(row));
            } catch(e) {}
        }

        // Auto-save on any input change (debounced) — skipped while editing an existing entry
        let saveTimer = null;
        if (!isEditing) {
            document.getElementById('linesTable').addEventListener('input', () => {
                clearTimeout(saveTimer);
                saveTimer = setTimeout(saveToLocalStorage, 600);
            });
            document.getElementById('linesTable').addEventListener('change', () => {
                clearTimeout(saveTimer);
                saveTimer = setTimeout(saveToLocalStorage, 300);
            });
        }

        // Clear draft after successful process (server sets flash)
        @if(session('clear_journal_draft'))
        localStorage.removeItem(LS_KEY);
        @endif

        // Restore draft on page load (instead of starting blank) — skipped when editing an existing entry
        if (isEditing) {
            existingLines.forEach(row => addRow(row));
        } else {
            const hasDraft = localStorage.getItem(LS_KEY);
            if (hasDraft) {
                restoreFromLocalStorage();
            } else {
                addRow();
            }
        }
        // ─────────────────────────────────────────────────────────────────────

        document.getElementById('addLine').addEventListener('click', function (e) {
            e.stopPropagation();
            addRow();
            if (!isEditing) saveToLocalStorage();
        });
        document.getElementById('loadSamples').addEventListener('click', () => {
            document.getElementById('linesBody').innerHTML = '';
            lineIndex = 0;
            samples.forEach(row => addRow(row));
            if (!isEditing) saveToLocalStorage();
        });
    })();
    </script>
    @endpush
</x-app-layout>
