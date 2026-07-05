<?php

namespace App\Services\Odoo;

use App\Models\JournalEntry;
use App\Models\JournalLine;
use RuntimeException;
use Illuminate\Support\Facades\Log;

/**
 * Pushes an approved journal entry to Odoo as an account.move.
 *
 * Flow:
 *   1. Resolve journal name → Odoo journal_id
 *   2. Resolve account for each line → Odoo account_id
 *   3. Create account.move (draft)
 *   4. Post it (action_post) so it lands in Odoo's posted state
 *   5. Update local entry: odoo_status = 'exported', odoo_move_id = <id>
 *
 * On any failure the entry keeps odoo_status = 'approved_for_odoo' and
 * odoo_push_error is populated so the admin can retry.
 */
class JournalOdooExporter
{
    private array $journalCache = [];
    private array $accountCache = [];

    public function __construct(private readonly OdooApiClient $odoo) {}

    public function push(JournalEntry $entry): void
    {
        $entry->load('lines.customer');

        $lines = $entry->lines;

        if ($lines->isEmpty()) {
            throw new RuntimeException('This journal entry has no lines to push to Odoo.');
        }

        $journalName = $entry->journal_name ?: 'General Journal';
        $journalId   = $this->resolveJournal($journalName);

        // Resolve partner from the first line that has a customer
        $partnerId = null;
        $partnerLine = $lines->first(fn ($l) => $l->customer_id || $l->customer_name);
        if ($partnerLine) {
            $partnerId = $this->resolvePartner(
                $partnerLine->customer?->name ?? $partnerLine->customer_name,
                $partnerLine->customer?->email,
                $partnerLine->customer?->phone,
            );
        }

        $lineIds = $lines->map(function (JournalLine $line) use ($entry) {
            $accountId = $this->resolveAccount($line);
            return [0, 0, [
                'name'       => $line->description ?: $entry->description,
                'debit'      => (float) $line->debit,
                'credit'     => (float) $line->credit,
                'account_id' => $accountId,
                'date'       => ($line->line_date ?? $entry->entry_date)->toDateString(),
            ]];
        })->values()->toArray();

        try {
            $movePayload = [
                'move_type'  => 'entry',
                'date'       => $entry->entry_date->toDateString(),
                'ref'        => $entry->reference,
                'journal_id' => $journalId,
                'narration'  => $entry->description,
                'line_ids'   => $lineIds,
            ];
            if ($partnerId) {
                $movePayload['partner_id'] = $partnerId;
            }

            $moveId = $this->odoo->create('account.move', $movePayload);

            // Post the move so it's confirmed in Odoo
            $this->odoo->callAction('account.move', [$moveId], 'action_post');

            // Fetch the Odoo move name (e.g. MISC/2026/07/0004)
            $moveInfo = $this->odoo->searchRead('account.move', [['id', '=', $moveId]], ['name'], 1);
            $moveName = $moveInfo[0]['name'] ?? null;

            $entry->update([
                'odoo_status'    => 'exported',
                'odoo_move_id'   => $moveId,
                'odoo_move_name' => $moveName,
                'odoo_push_error'=> null,
            ]);

            // Only mark "Yes" lines as Exported — "No" lines keep "Not exported"
            $entry->lines()->where('send_to_odoo', 'Yes')->update(['odoo_status' => 'Exported']);

            Log::info("[Odoo] Journal entry {$entry->reference} pushed as account.move id={$moveId}");

        } catch (\Throwable $e) {
            $entry->update(['odoo_push_error' => $e->getMessage()]);
            Log::error("[Odoo] Push failed for {$entry->reference}: " . $e->getMessage());
            throw $e;
        }
    }

    // -------------------------------------------------------------------------
    // Partner resolution
    // -------------------------------------------------------------------------

    private array $partnerCache = [];

    private function resolvePartner(?string $name, ?string $email = null, ?string $phone = null): ?int
    {
        if (! $name) return null;

        $key = strtolower($name);
        if (isset($this->partnerCache[$key])) return $this->partnerCache[$key];

        $results = $this->odoo->searchRead('res.partner', [['name', '=', $name]], ['id'], 1);

        if (empty($results) && $email) {
            $results = $this->odoo->searchRead('res.partner', [['email', '=', $email]], ['id'], 1);
        }

        if (empty($results)) {
            $partnerId = $this->odoo->create('res.partner', array_filter([
                'name'          => $name,
                'email'         => $email,
                'phone'         => $phone,
                'customer_rank' => 1,
            ]));
            Log::info("[Odoo] Created partner '{$name}' → id={$partnerId}");
        } else {
            $partnerId = $results[0]['id'];
            Log::info("[Odoo] Resolved partner '{$name}' → id={$partnerId}");
        }

        return $this->partnerCache[$key] = $partnerId;
    }

    // -------------------------------------------------------------------------
    // Journal resolution
    // -------------------------------------------------------------------------

    private function resolveJournal(string $name): int
    {
        if (isset($this->journalCache[$name])) {
            return $this->journalCache[$name];
        }

        // Exact match first
        $results = $this->odoo->searchRead('account.journal', [['name', '=', $name]], ['id', 'name'], 1);

        // Partial match fallback
        if (empty($results)) {
            $results = $this->odoo->searchRead('account.journal', [['name', 'ilike', $name]], ['id', 'name'], 1);
        }

        // Fall back to any "general" type journal
        if (empty($results)) {
            $results = $this->odoo->searchRead('account.journal', [['type', '=', 'general']], ['id', 'name'], 1);
        }

        if (empty($results)) {
            throw new RuntimeException(
                "Cannot find journal '{$name}' in Odoo. Please create it under Accounting → Configuration → Journals."
            );
        }

        $this->journalCache[$name] = $results[0]['id'];
        Log::info("[Odoo] Resolved journal '{$name}' → id={$results[0]['id']} ('{$results[0]['name']}')");

        return $results[0]['id'];
    }

    // -------------------------------------------------------------------------
    // Account resolution
    // -------------------------------------------------------------------------

    private function resolveAccount(JournalLine $line): int
    {
        // 1. Explicit account code on the line (highest priority)
        if (! empty($line->account_code)) {
            return $this->accountByCode($line->account_code);
        }

        // 2. Smart inference from event_type + debit/credit + provenance + description
        $inferred = $this->inferAccountCode($line);
        if ($inferred) {
            try {
                return $this->accountByCode($inferred);
            } catch (\Throwable $e) {
                Log::warning("[Odoo] Inferred code '{$inferred}' not in chart — {$e->getMessage()}");
            }
        }

        // 3. Explicit account name on the line
        if (! empty($line->account_name)) {
            return $this->accountByName($line->account_name);
        }

        // 4. Configurable suspense account (set ODOO_DEFAULT_ACCOUNT_CODE in .env)
        $defaultCode = config('odoo.default_account_code');
        if ($defaultCode) {
            return $this->accountByCode($defaultCode);
        }

        // 5. Last resort: suspense account by type
        $suspense = $this->odoo->searchRead(
            'account.account',
            [['account_type', '=', 'asset_current']],
            ['id', 'code', 'name'],
            1
        );
        if (! empty($suspense)) {
            Log::warning("[Odoo] No account mapped for '{$line->description}' — using suspense '{$suspense[0]['code']}'");
            return $suspense[0]['id'];
        }

        throw new RuntimeException(
            "Cannot resolve Odoo account for line '{$line->description}'. "
            . "Add account_code to the line or set ODOO_DEFAULT_ACCOUNT_CODE in .env."
        );
    }

    /**
     * Infer SYSCOHADA account code from event type, debit/credit side,
     * provenance, and description. All codes come from config/odoo.php
     * — no hard-coding, no user input required.
     */
    private function inferAccountCode(JournalLine $line): ?string
    {
        $a           = config('odoo.accounts');   // shorthand
        $event       = strtolower($line->event_type ?? '');
        $description = strtolower($line->description ?? '');
        $provenance  = strtolower($line->provenance_category ?? '');
        $isDebit     = ($line->debit ?? 0) > 0;

        // VAT / TVA line (any event type)
        if (str_contains($description, 'vat') || str_contains($description, 'tva')) {
            return $a['vat_output'];
        }

        // Provenance → which cash/bank account to use
        $cashCode = match(true) {
            str_contains($provenance, 'mobile money')         => $a['mobile_money'],
            str_contains($provenance, 'bank')                 => $a['bank'],
            str_contains($provenance, 'cash')                 => $a['cash'],
            str_contains($provenance, 'cool agristock agent') => $a['cash'],
            default                                            => $a['cash'],
        };

        // Service fee events
        if (str_contains($event, 'storage_fee')) {
            return $isDebit ? $a['receivable'] : $a['storage_revenue'];
        }
        if (str_contains($event, 'drying_fee')) {
            return $isDebit ? $a['receivable'] : $a['drying_revenue'];
        }
        if (str_contains($event, 'handling_fee')) {
            return $isDebit ? $a['receivable'] : $a['handling_revenue'];
        }

        // Payment received (customer settles invoice)
        if ($event === 'payment_received') {
            return $isDebit ? $cashCode : $a['receivable'];
        }

        // Advance payment (cash before invoice)
        if ($event === 'advance_payment') {
            return $isDebit ? $cashCode : $a['advance_received'];
        }

        // Refund (cash back to customer)
        if ($event === 'refund') {
            return $isDebit ? $a['advance_received'] : $cashCode;
        }

        // Credit note (reduce receivable)
        if ($event === 'credit_note') {
            return $isDebit ? $a['rebates_granted'] : $a['receivable'];
        }

        // Unknown / "Other" event type — use the configured catch-all account
        $catchAll = $isDebit
            ? ($a['other_debit']  ?? null)
            : ($a['other_credit'] ?? null);

        if ($catchAll) {
            Log::info("[Odoo] Unrecognised event type '{$line->event_type}' — using catch-all account {$catchAll}");
            return $catchAll;
        }

        return null;
    }

    private function accountByCode(string $code): int
    {
        $key = "code:{$code}";
        if (isset($this->accountCache[$key])) {
            return $this->accountCache[$key];
        }

        $results = $this->odoo->searchRead('account.account', [['code', '=', $code]], ['id', 'code', 'name'], 1);

        if (empty($results)) {
            throw new RuntimeException(
                "Account code '{$code}' not found in Odoo chart of accounts."
            );
        }

        $this->accountCache[$key] = $results[0]['id'];
        return $results[0]['id'];
    }

    private function accountByName(string $name): int
    {
        $key = "name:{$name}";
        if (isset($this->accountCache[$key])) {
            return $this->accountCache[$key];
        }

        $results = $this->odoo->searchRead('account.account', [['name', 'ilike', $name]], ['id', 'code', 'name'], 1);

        if (empty($results)) {
            throw new RuntimeException(
                "Account '{$name}' not found in Odoo chart of accounts."
            );
        }

        $this->accountCache[$key] = $results[0]['id'];
        return $results[0]['id'];
    }
}
