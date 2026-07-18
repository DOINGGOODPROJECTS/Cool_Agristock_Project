<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\JournalEntry;
use App\Services\Accounting\JournalService;
use App\Services\Odoo\JournalOdooExporter;
use Illuminate\Http\Request;

class JournalController extends Controller
{
    public function __construct(
        private JournalService      $journal,
        private JournalOdooExporter $odooExporter,
    ) {}

    public function index()
    {
        $entries = JournalEntry::with(['lines', 'createdBy', 'approvedBy', 'odooApprovedBy'])
            ->withCount('lines')
            ->orderByDesc('id')
            ->get();

        return view('accounting.journal', compact('entries'));
    }

    public function destroy(int $id)
    {
        $entry = JournalEntry::findOrFail($id);

        if (!in_array($entry->status, ['draft', 'rejected'])) {
            return back()->with('error', "Only draft or rejected entries can be deleted.");
        }

        $entry->lines()->delete();
        $entry->delete();

        return back()->with('success', "Journal entry {$entry->reference} deleted.");
    }

    public function ledger()
    {
        $entries = JournalEntry::with(['lines', 'createdBy'])
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->get()
            ->groupBy(fn ($e) => $e->entry_date->format('Y-m-d'));

        return view('accounting.journal-ledger', compact('entries'));
    }

    public function create()
    {
        $journalOptions = ['Purchase Journal', 'Sales Journal', 'Cash Journal', 'Bank Journal', 'General Journal', 'Other'];
        $eventTypes = ['storage_fee', 'drying_fee', 'handling_fee', 'storage_fee / drying_fee', 'refund', 'Other'];
        $provenanceOptions = ['Cash', 'Bank', 'Mobile money', 'COOL AGRISTOCK agent(Name of the person)'];
        $sendToOdooOptions = ['No', 'Yes', 'To review'];
        $odooStatusOptions = ['Not exported', 'Exported'];
        $sampleRows = $this->journalSampleRows();
        $customers = \App\Models\User::whereIn('group_id', [5, 6, 7, 8, 10])
            ->orderBy('name')->get(['id', 'name', 'phone']);

        return view('accounting.journal-entry-create', compact(
            'journalOptions',
            'eventTypes',
            'provenanceOptions',
            'sendToOdooOptions',
            'odooStatusOptions',
            'sampleRows',
            'customers'
        ));
    }

    public function store(Request $request)
    {
        try {
            $entry = $this->buildEntryFromRequest($request);
            return redirect()->route('accounting.journal.show', $entry->id)
                ->with('success', "Journal entry {$entry->reference} saved as draft.");
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function processEntry(Request $request)
    {
        try {
            $entry = $this->buildEntryFromRequest($request);

            // Auto-submit
            $this->journal->submit($entry);

            // Auto-approve
            $this->journal->approve($entry, auth()->id());

            // Push "Yes" lines to Odoo
            $hasYesLines = $entry->lines()->where('send_to_odoo', 'Yes')->exists();
            $odooMessage = '';

            if ($hasYesLines) {
                $this->journal->approveForOdoo($entry, auth()->id());
                try {
                    $this->odooExporter->push($entry);
                    $moveId = $entry->fresh()->odoo_move_id;
                    $odooMessage = " Pushed to Odoo (move #{$moveId}).";
                } catch (\Throwable $e) {
                    $odooMessage = " Odoo push failed: {$e->getMessage()}";
                }
            }

            // Clear localStorage key via flash so JS can clean up
            return redirect()->route('accounting.journal.show', $entry->id)
                ->with('success', "Entry {$entry->reference} processed.{$odooMessage}")
                ->with('clear_journal_draft', true);

        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function processLine(Request $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'line.line_date'           => ['required', 'date'],
            'line.journal'             => ['nullable', 'string', 'max:100'],
            'line.document_reference'  => ['nullable', 'string', 'max:255'],
            'line.event_type'          => ['required', 'string', 'max:100'],
            'line.provenance_category' => ['nullable', 'string', 'max:255'],
            'line.description'         => ['required', 'string', 'max:500'],
            'line.debit'               => ['required', 'numeric', 'min:0'],
            'line.credit'              => ['required', 'numeric', 'min:0'],
            'line.currency'            => ['required', 'string', 'max:3'],
            'line.send_to_odoo'        => ['required', 'string', 'max:50'],
            'line.account_code'        => ['nullable', 'string', 'max:50'],
            'line.customer_id'         => ['nullable', 'integer'],
            'line.customer_name'       => ['nullable', 'string', 'max:255'],
            'line.comments'            => ['nullable', 'string', 'max:1000'],
        ]);

        $line = $data['line'];

        try {
            $entry = $this->journal->createEntry(
                [
                    'description'         => $line['description'],
                    'entry_date'          => $line['line_date'],
                    'entry_type'          => 'manual',
                    'journal_name'        => $line['journal'] ?? null,
                    'document_reference'  => $line['document_reference'] ?? null,
                    'event_type'          => $line['event_type'],
                    'provenance_category' => $line['provenance_category'] ?? null,
                    'currency'            => $line['currency'],
                    'send_to_odoo'        => $line['send_to_odoo'],
                ],
                [[
                    'line_date'           => $line['line_date'],
                    'journal'             => $line['journal'] ?? null,
                    'document_reference'  => $line['document_reference'] ?? null,
                    'customer_id'         => ($line['customer_id'] ?? null) ?: null,
                    'customer_name'       => $line['customer_name'] ?? null,
                    'event_type'          => $line['event_type'],
                    'provenance_category' => $line['provenance_category'] ?? null,
                    'description'         => $line['description'],
                    'account_code'        => $line['account_code'] ?? '',
                    'account_name'        => '',
                    'label'               => $line['description'],
                    'debit'               => $line['debit'],
                    'credit'              => $line['credit'],
                    'currency'            => $line['currency'],
                    'send_to_odoo'        => $line['send_to_odoo'],
                    'odoo_status'         => 'Not exported',
                    'comments'            => $line['comments'] ?? null,
                ]],
                auth()->id(),
                skipBalanceCheck: true
            );

            $this->journal->submit($entry);
            $this->journal->approve($entry, auth()->id());

            $odooStatus = 'Not exported';

            if ($line['send_to_odoo'] === 'Yes') {
                $this->journal->approveForOdoo($entry, auth()->id());
                try {
                    $this->odooExporter->push($entry);
                    $odooStatus = 'Exported';
                } catch (\Throwable $e) {
                    return response()->json([
                        'success'     => false,
                        'reference'   => $entry->reference,
                        'odoo_status' => 'Not exported',
                        'message'     => "Saved as {$entry->reference} but Odoo push failed: {$e->getMessage()}",
                    ]);
                }
            }

            return response()->json([
                'success'     => true,
                'reference'   => $entry->reference,
                'odoo_status' => $odooStatus,
                'message'     => "Processed as {$entry->reference}.",
            ]);

        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    private function buildEntryFromRequest(Request $request): JournalEntry
    {
        [$entryData, $lineData] = $this->extractEntryPayload($request);

        return $this->journal->createEntry($entryData, $lineData, auth()->id());
    }

    private function extractEntryPayload(Request $request): array
    {
        $data = $request->validate([
            'lines'                         => ['required', 'array', 'min:2'],
            'lines.*.line_date'             => ['required', 'date'],
            'lines.*.journal'               => ['nullable', 'string', 'max:100'],
            'lines.*.document_reference'    => ['nullable', 'string', 'max:255'],
            'lines.*.event_type'            => ['required', 'string', 'max:100'],
            'lines.*.provenance_category'   => ['nullable', 'string', 'max:255'],
            'lines.*.description'           => ['required', 'string', 'max:500'],
            'lines.*.debit'                 => ['required', 'numeric', 'min:0'],
            'lines.*.credit'                => ['required', 'numeric', 'min:0'],
            'lines.*.currency'              => ['required', 'string', 'size:3'],
            'lines.*.send_to_odoo'          => ['required', 'string', 'max:50'],
            'lines.*.odoo_status'           => ['required', 'in:Not exported,Exported,In review'],
            'lines.*.account_code'          => ['nullable', 'string', 'max:50'],
            'lines.*.customer_id'           => ['nullable', 'integer'],
            'lines.*.customer_name'         => ['nullable', 'string', 'max:255'],
            'lines.*.comments'              => ['nullable', 'string', 'max:1000'],
        ]);

        $lines = collect($data['lines'])
            ->filter(fn ($line) => ($line['debit'] ?? 0) > 0 || ($line['credit'] ?? 0) > 0)
            ->values();

        if ($lines->count() < 2) {
            throw new \RuntimeException('Enter at least two debit/credit rows.');
        }

        $sendToOdoo  = $this->deriveSendToOdooDecision($lines->pluck('send_to_odoo')->all());
        $journals    = $lines->pluck('journal')->filter()->unique()->values();
        $eventTypes  = $lines->pluck('event_type')->filter()->unique()->values();
        $refs        = $lines->pluck('document_reference')->filter()->unique()->values();
        $lineCount   = $lines->count();

        $entryData = [
            'description'         => "{$lineCount}-line journal batch — " . $lines->pluck('description')->filter()->implode(', '),
            'entry_date'          => $lines->first()['line_date'],
            'entry_type'          => 'manual',
            'journal_name'        => $journals->count() === 1 ? $journals->first() : ($journals->implode(' / ') ?: null),
            'document_reference'  => $refs->count() === 1 ? $refs->first() : $refs->implode(', '),
            'event_type'          => $eventTypes->count() === 1 ? $eventTypes->first() : $eventTypes->implode(', '),
            'currency'            => $lines->first()['currency'],
            'send_to_odoo'        => $sendToOdoo,
            'comments'            => $lines->pluck('comments')->filter()->implode("\n"),
        ];

        $lineData = $lines->map(fn (array $line) => [
            'line_date'           => $line['line_date'],
            'journal'             => $line['journal'] ?? null,
            'document_reference'  => $line['document_reference'] ?? null,
            'customer_id'         => ($line['customer_id'] ?? null) ?: null,
            'customer_name'       => $line['customer_name'] ?? null,
            'event_type'          => $line['event_type'],
            'provenance_category' => $line['provenance_category'] ?? null,
            'description'         => $line['description'],
            'account_code'        => $line['account_code'] ?? '',
            'account_name'        => '',
            'label'               => $line['description'],
            'debit'               => $line['debit'],
            'credit'              => $line['credit'],
            'currency'            => $line['currency'],
            'send_to_odoo'        => $line['send_to_odoo'],
            'odoo_status'         => $line['odoo_status'],
            'comments'            => $line['comments'] ?? null,
        ])->all();

        return [$entryData, $lineData];
    }

    public function edit(int $id)
    {
        $entry = JournalEntry::with('lines')->findOrFail($id);

        if ($entry->status !== 'draft') {
            return redirect()->route('accounting.journal.show', $id)
                ->with('error', 'Only draft entries can be edited.');
        }

        $journalOptions = ['Purchase Journal', 'Sales Journal', 'Cash Journal', 'Bank Journal', 'General Journal', 'Other'];
        $eventTypes = ['storage_fee', 'drying_fee', 'handling_fee', 'storage_fee / drying_fee', 'refund', 'Other'];
        $provenanceOptions = ['Cash', 'Bank', 'Mobile money', 'COOL AGRISTOCK agent(Name of the person)'];
        $sendToOdooOptions = ['No', 'Yes', 'To review'];
        $odooStatusOptions = ['Not exported', 'Exported'];
        $sampleRows = $this->journalSampleRows();
        $customers = \App\Models\User::whereIn('group_id', [5, 6, 7, 8, 10])
            ->orderBy('name')->get(['id', 'name', 'phone']);

        return view('accounting.journal-entry-create', compact(
            'entry',
            'journalOptions',
            'eventTypes',
            'provenanceOptions',
            'sendToOdooOptions',
            'odooStatusOptions',
            'sampleRows',
            'customers'
        ));
    }

    public function update(Request $request, int $id)
    {
        $entry = JournalEntry::findOrFail($id);

        if ($entry->status !== 'draft') {
            return back()->with('error', 'Only draft entries can be edited.');
        }

        try {
            [$entryData, $lineData] = $this->extractEntryPayload($request);
            $this->journal->updateEntry($entry, $entryData, $lineData);

            return redirect()->route('accounting.journal.show', $entry->id)
                ->with('success', "Journal entry {$entry->reference} updated.");
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    private function deriveSendToOdooDecision(array $decisions): string
    {
        if (in_array('Hold for Finance', $decisions, true) || in_array('To review', $decisions, true)) {
            return 'Hold for Finance';
        }

        if (in_array('Yes', $decisions, true)) {
            return 'Yes';
        }

        if (in_array('No', $decisions, true)) {
            return 'No';
        }

        return 'Not applicable';
    }

    private function journalSampleRows(): array
    {
        return [
            ['line_date' => now()->toDateString(), 'journal' => 'Sales Journal', 'document_reference' => 'INV-STO-0001', 'event_type' => 'storage_fee',           'provenance_category' => '',   'description' => 'Customer receivable — storage fee', 'debit' => 118000, 'credit' => 0,      'currency' => 'XOF', 'send_to_odoo' => 'Yes', 'odoo_status' => 'Not exported', 'comments' => ''],
            ['line_date' => now()->toDateString(), 'journal' => 'Sales Journal', 'document_reference' => 'INV-STO-0001', 'event_type' => 'storage_fee',           'provenance_category' => '',   'description' => 'Storage revenue',                   'debit' => 0,      'credit' => 100000, 'currency' => 'XOF', 'send_to_odoo' => 'Yes', 'odoo_status' => 'Not exported', 'comments' => ''],
            ['line_date' => now()->toDateString(), 'journal' => 'Sales Journal', 'document_reference' => 'INV-STO-0001', 'event_type' => 'storage_fee',           'provenance_category' => '',   'description' => 'Output VAT (TVA collectée)',         'debit' => 0,      'credit' => 18000,  'currency' => 'XOF', 'send_to_odoo' => 'No',  'odoo_status' => 'Not exported', 'comments' => ''],
            ['line_date' => now()->toDateString(), 'journal' => 'Cash Journal',  'document_reference' => 'REF-0001',     'event_type' => 'refund',                 'provenance_category' => 'Cash', 'description' => 'Customer refund — credit balance',  'debit' => 50000,  'credit' => 0,      'currency' => 'XOF', 'send_to_odoo' => 'Yes', 'odoo_status' => 'Not exported', 'comments' => ''],
            ['line_date' => now()->toDateString(), 'journal' => 'Cash Journal',  'document_reference' => 'REF-0001',     'event_type' => 'refund',                 'provenance_category' => 'Cash', 'description' => 'Cash paid out to customer',         'debit' => 0,      'credit' => 50000,  'currency' => 'XOF', 'send_to_odoo' => 'Yes', 'odoo_status' => 'Not exported', 'comments' => ''],
        ];
    }

    public function show(int $id)
    {
        $entry = JournalEntry::with(['lines', 'createdBy', 'approvedBy', 'postedBy', 'odooApprovedBy', 'financialEvent'])
            ->findOrFail($id);

        return view('accounting.journal-entry', compact('entry'));
    }

    public function submit(int $id)
    {
        $entry = JournalEntry::findOrFail($id);

        if ($entry->created_by !== auth()->id() && auth()->user()->group_id > 1) {
            abort(403);
        }

        try {
            $this->journal->submit($entry);
            return back()->with('success', 'Entry submitted for admin approval.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    // ── Admin-only actions ────────────────────────────────────────────────────

    public function approve(int $id)
    {
        $entry = JournalEntry::findOrFail($id);

        try {
            $this->journal->approve($entry, auth()->id());
            return back()->with('success', "Entry {$entry->reference} approved.");
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function reject(int $id)
    {
        $entry = JournalEntry::findOrFail($id);

        try {
            $this->journal->reject($entry, auth()->id());
            return back()->with('success', "Entry {$entry->reference} rejected.");
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function approveOdoo(int $id)
    {
        $entry = JournalEntry::findOrFail($id);

        try {
            $this->journal->approveForOdoo($entry, auth()->id());
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        try {
            $this->odooExporter->push($entry);
            return back()->with('success', "Entry {$entry->reference} pushed to Odoo (move id: {$entry->fresh()->odoo_move_id}).");
        } catch (\Throwable $e) {
            return back()->with('error', "Approved for Odoo but push failed: {$e->getMessage()}");
        }
    }

    public function rejectOdoo(Request $request, int $id)
    {
        $request->validate(['reason' => ['required', 'string', 'max:500']]);

        $entry = JournalEntry::findOrFail($id);

        try {
            $this->journal->rejectFromOdoo($entry, auth()->id(), $request->reason);
            return back()->with('success', "Odoo export rejected for entry {$entry->reference}.");
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
