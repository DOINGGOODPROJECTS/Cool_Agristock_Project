<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Billing;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\User;
use App\Services\Accounting\InvoiceGeneratorService;
use App\Services\Odoo\InvoiceOdooExporter;
use Codedge\Fpdf\Fpdf\Fpdf;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function __construct(
        private InvoiceGeneratorService $generator,
        private InvoiceOdooExporter     $odooExporter,
    ) {}

    public function index()
    {
        $query = Invoice::with(['customer', 'billing', 'generatedBy', 'lines'])
            ->orderByDesc('invoice_date')
            ->orderByDesc('id');

        if (auth()->user()->group_id > 4) {
            $query->forCustomer(auth()->id());
        }

        $invoices = $query->get()->groupBy(fn ($inv) => $inv->invoice_date->format('Y-m-d'));

        return view('accounting.invoices', compact('invoices'));
    }

    public function destroy(int $id)
    {
        $invoice = Invoice::findOrFail($id);

        if ($invoice->status !== 'draft') {
            return back()->with('error', 'Only draft invoices can be deleted.');
        }

        $invoice->lines()->delete();
        $invoice->delete();

        return back()->with('success', "Invoice {$invoice->invoice_number} deleted.");
    }

    public function show(int $id)
    {
        $invoice = Invoice::with(['customer', 'billing.stock', 'generatedBy', 'lines'])->findOrFail($id);

        if (auth()->user()->group_id > 4 && $invoice->customer_id !== auth()->id()) {
            abort(403);
        }

        return view('accounting.invoice', compact('invoice'));
    }

    public function create()
    {
        // Groups 5–10 are customer/partner types (Coopérative Agricole, Pêche, Grossiste, Entreprises, Particulier, etc.)
        $customers = User::whereIn('group_id', [5, 6, 7, 8, 10])
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'phone', 'group_id']);
        $eventTypeOptions = ['storage_fee', 'drying_fee', 'handling_fee', 'storage_fee / drying_fee', 'refund', 'Other'];
        $categoryOptions = ['Cereals', 'Legumes', 'Root & Tubers', 'Vegetables', 'Fruits', 'Cash Crops'];
        $productOptions = [
            'Maize',
            'Rice',
            'Millet',
            'Sorghum',
            'Wheat',
            'Groundnut',
            'Cowpeas',
            'Soybean',
            'Black-eyed Peas',
            'Cassava',
            'Yam',
            'Sweet Potato',
            'Cocoyam',
            'Tomato',
            'Onion',
            'Pepper',
            'Cabbage',
            'Carrot',
            'Mango',
            'Banana',
            'Plantain',
            'Pawpaw',
            'Pineapple',
            'Cocoa',
            'Cashew',
            'Shea Butter',
            'Cotton',
            'Atieke',
        ];
        $unitOptions = ['Bag/sack', 'Tonne', 'Kilogram', 'Crate', 'Carton'];
        $sendToOdooOptions = ['No', 'Yes', 'To review'];
        $headerSendToOdooOptions = ['Yes', 'No'];
        $financeStatusOptions = ['Draft', 'To review', 'Finance approved', 'Blocked', 'Exported to Odoo'];
        $sampleRows = $this->invoiceSampleRows();

        return view('accounting.invoice-generator', compact(
            'customers',
            'eventTypeOptions',
            'categoryOptions',
            'productOptions',
            'unitOptions',
            'sendToOdooOptions',
            'headerSendToOdooOptions',
            'sampleRows',
            'financeStatusOptions'
        ));
    }

    private function validateInvoiceRequest(Request $request, ?int $invoiceId = null): array
    {
        return $request->validate([
            'invoice_number'                  => ['nullable', 'string', 'max:255', 'unique:invoices,invoice_number' . ($invoiceId ? ",{$invoiceId}" : '')],
            'invoice_date'                    => ['required', 'date'],
            'currency'                        => ['required', 'string', 'size:3'],
            'customer_id'                     => ['nullable', 'exists:users,id'],
            'customer_name'                   => ['nullable', 'string', 'max:255'],
            'due_date'                        => ['nullable', 'date'],
            'stock_lot'                       => ['nullable', 'string', 'max:255'],
            'payment_terms'                   => ['nullable', 'string', 'max:255'],
            'odoo_partner_ref'                => ['nullable', 'string', 'max:255'],
            'finance_status'                  => ['required', 'string', 'max:50'],
            'send_to_odoo'                    => ['required', 'string', 'max:50'],
            'odoo_decision_reason'            => ['nullable', 'string', 'max:1000'],
            'notes'                           => ['nullable', 'string', 'max:1000'],
            'lines'                           => ['required', 'array', 'min:1'],
            'lines.*.service'                 => ['required', 'string', 'max:100'],
            'lines.*.category'                => ['nullable', 'string', 'max:255'],
            'lines.*.product'                 => ['nullable', 'string', 'max:255'],
            'lines.*.description'             => ['nullable', 'string', 'max:500'],
            'lines.*.unit'                    => ['nullable', 'string', 'max:50'],
            'lines.*.quantity'                => ['required', 'numeric', 'min:0'],
            'lines.*.unit_price'              => ['required', 'numeric', 'min:0'],
            'lines.*.discount_fixed_amount'   => ['nullable', 'numeric', 'min:0'],
            'lines.*.vat_rate'                => ['nullable', 'numeric', 'min:0'],
            'lines.*.journal_entry_no'        => ['nullable', 'string', 'max:255'],
            'lines.*.send_to_odoo'            => ['required', 'string', 'max:50'],
            'lines.*.odoo_decision_reason'    => ['nullable', 'string', 'max:1000'],
            'lines.*.comments'                => ['nullable', 'string', 'max:1000'],
        ]);
    }

    /**
     * Compute per-line totals (amount before VAT, VAT amount, line total) plus
     * the invoice-level subtotal/tax/total, from the raw validated line rows.
     *
     * @return array{0: \Illuminate\Support\Collection, 1: float, 2: float, 3: float}
     */
    private function computeLinePayload(array $rawLines): array
    {
        $lines = collect($rawLines)
            ->filter(fn ($line) => trim((string) ($line['service'] ?? '')) !== '')
            ->values();

        $subtotal = 0;
        $taxAmount = 0;
        $totalAmount = 0;

        $linePayload = $lines->map(function (array $line, int $index) use (&$subtotal, &$taxAmount, &$totalAmount) {
            $quantity = (float) $line['quantity'];
            $unitPrice = (float) $line['unit_price'];
            $discount = (float) ($line['discount_fixed_amount'] ?? 0);
            $vatRate = (float) ($line['vat_rate'] ?? 0);
            $amountBeforeVat = max(($quantity * $unitPrice) - $discount, 0);
            $vatAmount = round($amountBeforeVat * $vatRate, 2);
            $lineTotal = $amountBeforeVat + $vatAmount;

            $subtotal += $amountBeforeVat;
            $taxAmount += $vatAmount;
            $totalAmount += $lineTotal;

            return [
                'line_no'                 => $index + 1,
                'service'                 => $line['service'],
                'category'                => $line['category'] ?? null,
                'product'                 => $line['product'] ?? null,
                'description'             => $line['description'] ?? null,
                'unit'                    => $line['unit'] ?? null,
                'quantity'                => $quantity,
                'unit_price'              => $unitPrice,
                'discount_fixed_amount'   => $discount,
                'amount_before_vat'       => $amountBeforeVat,
                'vat_rate'                => $vatRate,
                'vat_amount'              => $vatAmount,
                'total_amount'            => $lineTotal,
                'journal_entry_no'        => $line['journal_entry_no'] ?? null,
                'send_to_odoo'            => $line['send_to_odoo'],
                'odoo_decision_reason'    => $line['odoo_decision_reason'] ?? null,
                'comments'                => $line['comments'] ?? null,
            ];
        });

        return [$linePayload, $subtotal, $taxAmount, $totalAmount];
    }

    public function storeManual(Request $request)
    {
        $data = $this->validateInvoiceRequest($request);

        [$linePayload, $subtotal, $taxAmount, $totalAmount] = $this->computeLinePayload($data['lines']);

        if ($linePayload->isEmpty()) {
            return back()->withInput()->with('error', 'Add at least one invoice line.');
        }

        try {
            $invoice = Invoice::create([
                'invoice_number'       => ($data['invoice_number'] ?? null) ?: Invoice::nextInvoiceNumber(),
                'billing_id'           => null,
                'customer_id'          => $data['customer_id'] ?: null,
                'customer_name'        => $data['customer_name'] ?? null,
                'invoice_date'         => $data['invoice_date'],
                'due_date'             => $data['due_date'] ?? null,
                'stock_lot'            => $data['stock_lot'] ?? null,
                'payment_terms'        => $data['payment_terms'] ?? null,
                'odoo_partner_ref'     => $data['odoo_partner_ref'] ?? null,
                'subtotal'             => $subtotal,
                'tax_amount'           => $taxAmount,
                'total_amount'         => $totalAmount,
                'currency'             => $data['currency'],
                'status'               => 'draft',
                'finance_status'       => $data['finance_status'],
                'send_to_odoo'         => $data['send_to_odoo'],
                'odoo_decision_reason' => $data['odoo_decision_reason'] ?? null,
                'accounting_check'     => 'OK',
                'notes'                => $data['notes'] ?? null,
                'generated_by'         => auth()->id(),
            ]);

            $invoice->lines()->createMany($linePayload->all());

            $action = $request->input('_action', 'generate');

            if ($action === 'send_odoo') {
                // Force all lines to Yes so exporter picks them all up
                $invoice->lines()->update(['send_to_odoo' => 'Yes']);
                $invoice->update(['send_to_odoo' => 'Yes']);
                try {
                    $this->odooExporter->push($invoice);
                    $fresh = $invoice->fresh();
                    return redirect()->route('accounting.invoices.show', $invoice->id)
                        ->with('success', "Invoice {$invoice->invoice_number} sent to Odoo as {$fresh->odoo_invoice_name}.");
                } catch (\Throwable $e) {
                    return redirect()->route('accounting.invoices.show', $invoice->id)
                        ->with('error', "Invoice saved but Odoo push failed: {$e->getMessage()}");
                }
            }

            return redirect()->route('accounting.invoices.pdf', $invoice->id)
                ->with('success', "Invoice {$invoice->invoice_number} generated.");
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function edit(int $id)
    {
        $invoice = Invoice::with('lines')->findOrFail($id);

        if ($invoice->status !== 'draft') {
            return redirect()->route('accounting.invoices.show', $id)
                ->with('error', 'Only draft invoices can be edited.');
        }

        $customers = User::whereIn('group_id', [5, 6, 7, 8, 10])
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'phone', 'group_id']);
        $eventTypeOptions = ['storage_fee', 'drying_fee', 'handling_fee', 'storage_fee / drying_fee', 'refund', 'Other'];
        $categoryOptions = ['Cereals', 'Legumes', 'Root & Tubers', 'Vegetables', 'Fruits', 'Cash Crops'];
        $productOptions = [
            'Maize', 'Rice', 'Millet', 'Sorghum', 'Wheat', 'Groundnut', 'Cowpeas', 'Soybean',
            'Black-eyed Peas', 'Cassava', 'Yam', 'Sweet Potato', 'Cocoyam', 'Tomato', 'Onion',
            'Pepper', 'Cabbage', 'Carrot', 'Mango', 'Banana', 'Plantain', 'Pawpaw', 'Pineapple',
            'Cocoa', 'Cashew', 'Shea Butter', 'Cotton', 'Atieke',
        ];
        $unitOptions = ['Bag/sack', 'Tonne', 'Kilogram', 'Crate', 'Carton'];
        $sendToOdooOptions = ['No', 'Yes', 'To review'];
        $headerSendToOdooOptions = ['Yes', 'No'];
        $financeStatusOptions = ['Draft', 'To review', 'Finance approved', 'Blocked', 'Exported to Odoo'];
        $sampleRows = $this->invoiceSampleRows();

        return view('accounting.invoice-generator', compact(
            'invoice',
            'customers',
            'eventTypeOptions',
            'categoryOptions',
            'productOptions',
            'unitOptions',
            'sendToOdooOptions',
            'headerSendToOdooOptions',
            'sampleRows',
            'financeStatusOptions'
        ));
    }

    public function update(Request $request, int $id)
    {
        $invoice = Invoice::findOrFail($id);

        if ($invoice->status !== 'draft') {
            return back()->with('error', 'Only draft invoices can be edited.');
        }

        $data = $this->validateInvoiceRequest($request, $invoice->id);

        [$linePayload, $subtotal, $taxAmount, $totalAmount] = $this->computeLinePayload($data['lines']);

        if ($linePayload->isEmpty()) {
            return back()->withInput()->with('error', 'Add at least one invoice line.');
        }

        try {
            $invoice->update([
                'invoice_number'       => ($data['invoice_number'] ?? null) ?: $invoice->invoice_number,
                'customer_id'          => $data['customer_id'] ?: null,
                'customer_name'        => $data['customer_name'] ?? null,
                'invoice_date'         => $data['invoice_date'],
                'due_date'             => $data['due_date'] ?? null,
                'stock_lot'            => $data['stock_lot'] ?? null,
                'payment_terms'        => $data['payment_terms'] ?? null,
                'odoo_partner_ref'     => $data['odoo_partner_ref'] ?? null,
                'subtotal'             => $subtotal,
                'tax_amount'           => $taxAmount,
                'total_amount'         => $totalAmount,
                'currency'             => $data['currency'],
                'finance_status'       => $data['finance_status'],
                'send_to_odoo'         => $data['send_to_odoo'],
                'odoo_decision_reason' => $data['odoo_decision_reason'] ?? null,
                'notes'                => $data['notes'] ?? null,
            ]);

            $invoice->lines()->delete();
            $invoice->lines()->createMany($linePayload->all());

            return redirect()->route('accounting.invoices.show', $invoice->id)
                ->with('success', "Invoice {$invoice->invoice_number} updated.");
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function generate(Request $request)
    {
        $request->validate(['billing_id' => ['required', 'exists:billings,id']]);

        $billing = Billing::findOrFail($request->billing_id);

        try {
            $invoice = $this->generator->generateFromBilling($billing, auth()->id());
            return redirect()->route('accounting.invoices.show', $invoice->id)
                ->with('success', "Invoice {$invoice->invoice_number} generated successfully.");
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function pdf(int $id)
    {
        $invoice = Invoice::with(['lines', 'customer'])->findOrFail($id);
        $this->generateInvoicePdf($invoice, $invoice->lines->all());
    }

    public function linePdf(int $id, int $lineId)
    {
        $invoice = Invoice::with('customer')->findOrFail($id);
        $line    = InvoiceLine::where('invoice_id', $id)->findOrFail($lineId);
        $this->generateInvoicePdf($invoice, [$line]);
    }

    public function processLine(Request $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'line.service'               => ['required', 'string', 'max:100'],
            'line.category'              => ['nullable', 'string', 'max:255'],
            'line.product'               => ['nullable', 'string', 'max:255'],
            'line.description'           => ['nullable', 'string', 'max:500'],
            'line.unit'                  => ['nullable', 'string', 'max:50'],
            'line.quantity'              => ['required', 'numeric', 'min:0'],
            'line.unit_price'            => ['required', 'numeric', 'min:0'],
            'line.discount_fixed_amount' => ['nullable', 'numeric', 'min:0'],
            'line.vat_rate'              => ['nullable', 'numeric', 'min:0'],
            'line.journal_entry_no'      => ['nullable', 'string', 'max:255'],
            'line.send_to_odoo'          => ['required', 'string', 'max:50'],
            'line.comments'              => ['nullable', 'string', 'max:1000'],
            'line.customer_id'           => ['nullable', 'exists:users,id'],
            'line.customer_name'         => ['nullable', 'string', 'max:255'],
            'line.invoice_date'          => ['nullable', 'date'],
            'line.currency'              => ['nullable', 'string', 'size:3'],
        ]);

        $line = $data['line'];
        $qty         = (float) ($line['quantity'] ?? 0);
        $unitPrice   = (float) ($line['unit_price'] ?? 0);
        $discount    = (float) ($line['discount_fixed_amount'] ?? 0);
        $vatRate     = (float) ($line['vat_rate'] ?? 0);
        $beforeVat   = max(($qty * $unitPrice) - $discount, 0);
        $vatAmount   = round($beforeVat * $vatRate, 2);
        $lineTotal   = $beforeVat + $vatAmount;

        try {
            $invoice = Invoice::create([
                'invoice_number'  => Invoice::nextInvoiceNumber(),
                'billing_id'      => null,
                'customer_id'     => $line['customer_id'] ?: null,
                'customer_name'   => $line['customer_name'] ?? null,
                'invoice_date'    => $line['invoice_date'] ?? now()->toDateString(),
                'currency'        => $line['currency'] ?? 'XOF',
                'subtotal'        => $beforeVat,
                'tax_amount'      => $vatAmount,
                'total_amount'    => $lineTotal,
                'status'          => 'draft',
                'finance_status'  => 'To review',
                'send_to_odoo'    => $line['send_to_odoo'],
                'odoo_status'     => 'not_exported',
                'accounting_check'=> 'OK',
                'generated_by'    => auth()->id(),
            ]);

            $invoice->lines()->create([
                'line_no'               => 1,
                'service'               => $line['service'],
                'category'              => $line['category'] ?? null,
                'product'               => $line['product'] ?? null,
                'description'           => $line['description'] ?? null,
                'unit'                  => $line['unit'] ?? null,
                'quantity'              => $qty,
                'unit_price'            => $unitPrice,
                'discount_fixed_amount' => $discount,
                'amount_before_vat'     => $beforeVat,
                'vat_rate'              => $vatRate,
                'vat_amount'            => $vatAmount,
                'total_amount'          => $lineTotal,
                'journal_entry_no'      => $line['journal_entry_no'] ?? null,
                'send_to_odoo'          => $line['send_to_odoo'],
                'comments'              => $line['comments'] ?? null,
            ]);

            return response()->json([
                'success'     => true,
                'invoice_id'  => $invoice->id,
                'invoice_no'  => $invoice->invoice_number,
                'pdf_url'     => route('accounting.invoices.pdf', $invoice->id),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    private function generateInvoicePdf(Invoice $invoice, array $lines): void
    {
        $customerName  = $invoice->customer?->name ?? $invoice->customer_name ?? 'N/A';
        $customerPhone = $invoice->customer?->phone ?? '';
        $customerEmail = $invoice->customer?->email ?? '';

        $pdf = new Fpdf('P', 'mm', 'A4');
        $pdf->SetMargins(12, 12, 12);
        $pdf->AddPage();

        // ── Header ──────────────────────────────────────────────────────────
        $pdf->SetFont('Arial', 'B', 18);
        $pdf->SetTextColor(78, 122, 70);
        $pdf->Cell(130, 8, 'COOL AGRISTOCK', 0, 0);
        $pdf->SetFont('Arial', 'B', 22);
        $pdf->SetTextColor(50, 50, 50);
        $pdf->Cell(56, 8, utf8_decode('FACTURE'), 0, 1, 'R');

        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(120, 120, 120);
        $pdf->Cell(130, 5, utf8_decode('Gestion de Stockage Frigorifique'), 0, 0);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetTextColor(50, 50, 50);
        $pdf->Cell(56, 5, utf8_decode($invoice->invoice_number), 0, 1, 'R');

        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(120, 120, 120);
        $pdf->Cell(130, 4, utf8_decode('SIS A LA RIVIERA FAYA - COCODY, Abidjan'), 0, 0);
        $pdf->Cell(56, 4, utf8_decode('Date : ') . $invoice->invoice_date->format('d/m/Y'), 0, 1, 'R');
        if ($invoice->due_date) {
            $pdf->Cell(130, 4, '', 0, 0);
            $pdf->Cell(56, 4, utf8_decode('Échéance : ') . $invoice->due_date->format('d/m/Y'), 0, 1, 'R');
        }

        $pdf->Ln(4);
        $pdf->SetDrawColor(78, 122, 70);
        $pdf->SetLineWidth(0.5);
        $pdf->Line(12, $pdf->GetY(), 198, $pdf->GetY());
        $pdf->Ln(4);

        // ── Bill To ─────────────────────────────────────────────────────────
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetTextColor(120, 120, 120);
        $pdf->Cell(90, 4, utf8_decode('FACTURÉ À'), 0, 1);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetTextColor(30, 30, 30);
        $pdf->Cell(90, 5, utf8_decode(strtoupper($customerName)), 0, 1);
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(80, 80, 80);
        if ($customerPhone) $pdf->Cell(90, 4, utf8_decode('Tél : ') . $customerPhone, 0, 1);
        if ($customerEmail) $pdf->Cell(90, 4, 'Email : ' . $customerEmail, 0, 1);

        $pdf->Ln(5);

        // ── Line items table ─────────────────────────────────────────────────
        $pdf->SetFillColor(78, 122, 70);
        $pdf->SetTextColor(255);
        $pdf->SetFont('Arial', 'B', 7);
        $pdf->SetLineWidth(0.1);

        $w = [28, 20, 15, 38, 14, 18, 16, 20, 10];
        $headers = array_map('utf8_decode', ["Type d'événement", 'Produit', 'Unité', 'Description', 'Qté', 'Prix Unit.', 'Remise', 'Avant TVA', 'TVA%']);
        foreach ($headers as $i => $h) {
            $pdf->Cell($w[$i], 7, $h, 1, 0, 'C', true);
        }
        $pdf->Ln();

        $pdf->SetTextColor(30, 30, 30);
        $pdf->SetFont('Arial', '', 7);
        $fill = false;
        $subtotal = $taxTotal = $grandTotal = 0;

        foreach ($lines as $line) {
            $pdf->SetFillColor($fill ? 240 : 255, $fill ? 245 : 255, $fill ? 240 : 255);
            $vatPct = rtrim(rtrim(number_format((float)$line->vat_rate * 100, 1), '0'), '.') . '%';
            $pdf->Cell($w[0], 6, utf8_decode($line->service ?? ''), 'LR', 0, 'L', $fill);
            $pdf->Cell($w[1], 6, utf8_decode($line->product ?? ''), 'LR', 0, 'C', $fill);
            $pdf->Cell($w[2], 6, utf8_decode($line->unit ?? ''), 'LR', 0, 'C', $fill);
            $pdf->Cell($w[3], 6, utf8_decode(mb_substr($line->description ?? '', 0, 32)), 'LR', 0, 'L', $fill);
            $pdf->Cell($w[4], 6, number_format($line->quantity, 2, '.', ' '), 'LR', 0, 'R', $fill);
            $pdf->Cell($w[5], 6, number_format($line->unit_price, 0, '.', ' '), 'LR', 0, 'R', $fill);
            $pdf->Cell($w[6], 6, number_format($line->discount_fixed_amount, 0, '.', ' '), 'LR', 0, 'R', $fill);
            $pdf->Cell($w[7], 6, number_format($line->amount_before_vat, 0, '.', ' '), 'LR', 0, 'R', $fill);
            $pdf->Cell($w[8], 6, $vatPct, 'LR', 0, 'C', $fill);
            $pdf->Ln();
            $fill = !$fill;
            $subtotal   += (float) $line->amount_before_vat;
            $taxTotal   += (float) $line->vat_amount;
            $grandTotal += (float) $line->total_amount;
        }
        $pdf->Cell(array_sum($w), 0, '', 'T');
        $pdf->Ln(5);

        // ── Totals ───────────────────────────────────────────────────────────
        $tw = array_sum($w);
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(60, 60, 60);
        $pdf->Cell($tw - 50, 6, '', 0, 0);
        $pdf->Cell(25, 6, utf8_decode('Sous-total :'), 0, 0, 'R');
        $pdf->Cell(25, 6, number_format($subtotal, 0, '.', ' ') . ' XOF', 0, 1, 'R');

        $pdf->Cell($tw - 50, 6, '', 0, 0);
        $pdf->Cell(25, 6, 'TVA :', 0, 0, 'R');
        $pdf->Cell(25, 6, number_format($taxTotal, 0, '.', ' ') . ' XOF', 0, 1, 'R');

        $pdf->SetFont('Arial', 'B', 11);
        $pdf->SetFillColor(78, 122, 70);
        $pdf->SetTextColor(255);
        $pdf->Cell($tw - 50, 7, '', 0, 0);
        $pdf->Cell(25, 7, utf8_decode('TOTAL'), 1, 0, 'R', true);
        $pdf->Cell(25, 7, number_format($grandTotal, 0, '.', ' ') . ' XOF', 1, 1, 'R', true);

        $pdf->Ln(8);
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(80, 80, 80);
        $pdf->Cell(93, 5, utf8_decode('Signature (Client) : _______________________'), 0, 0);
        $pdf->Cell(93, 5, utf8_decode('Signature (Cool AgriStock) : _______________________'), 0, 1, 'R');

        // ── Footer ───────────────────────────────────────────────────────────
        $pdf->SetY(-18);
        $pdf->SetFont('Arial', 'I', 7);
        $pdf->SetTextColor(150, 150, 150);
        $pdf->Cell(186, 4, utf8_decode('SIS A LA RIVIERA FAYA - ROND POINT CITE SIR - COCODY, Abidjan - Côte d\'Ivoire'), 0, 1, 'C');
        $pdf->Cell(186, 4, utf8_decode('Tél : (+225) 0102030405 | www.cool-agristock.com'), 0, 1, 'C');

        $pdf->Output('I', 'invoice-' . $invoice->invoice_number . '.pdf');
        exit;
    }

    private function invoiceSampleRows(): array
    {
        return [
            [
                'service'               => 'storage_fee',
                'category'              => 'Cereals',
                'product'               => 'Maize',
                'description'           => 'Monthly storage fee — 100 bags of maize',
                'unit'                  => 'Bag/sack',
                'quantity'              => 100,
                'unit_price'            => 1500,
                'discount_fixed_amount' => 0,
                'vat_rate'              => 0.18,
                'journal_entry_no'      => '',
                'send_to_odoo'          => 'Yes',
                'comments'              => '',
            ],
            [
                'service'               => 'drying_fee',
                'category'              => 'Cash Crops',
                'product'               => 'Rice',
                'description'           => 'Drying service — 5 tonnes of paddy rice',
                'unit'                  => 'Tonne',
                'quantity'              => 5,
                'unit_price'            => 12000,
                'discount_fixed_amount' => 5000,
                'vat_rate'              => 0.18,
                'journal_entry_no'      => '',
                'send_to_odoo'          => 'Yes',
                'comments'              => '',
            ],
            [
                'service'               => 'handling_fee',
                'category'              => 'Legumes',
                'product'               => 'Groundnut',
                'description'           => 'Loading and handling — groundnut bags',
                'unit'                  => 'Bag/sack',
                'quantity'              => 50,
                'unit_price'            => 800,
                'discount_fixed_amount' => 0,
                'vat_rate'              => 0,
                'journal_entry_no'      => '',
                'send_to_odoo'          => 'No',
                'comments'              => '',
            ],
        ];
    }
}
