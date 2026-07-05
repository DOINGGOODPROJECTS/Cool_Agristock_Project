<?php

namespace App\Services\Export;

use App\Models\Billing;
use App\Models\FinancialEvent;
use App\Models\Payment;
use App\Models\User;

/**
 * CSV-07: Pre-export validation.
 *
 * Runs before any file is generated and returns a structured report of
 * all rows that would fail Odoo import. Callers should block export when
 * $report['blocking_errors'] > 0.
 */
class ExportValidator
{
    /**
     * Validate a financial event is not blocked due to rejected Chart of Accounts.
     * Returns a blocking issue array if blocked, or null if clear.
     */
    public function checkAccountBlock(FinancialEvent $event): ?array
    {
        $blocked = config('accounting_events.export_blocked_event_types', []);

        if (in_array($event->event_type, $blocked, true)) {
            return $this->issue(
                'blocking',
                $event->event_type,
                $event->id,
                "Event type [{$event->event_type}] is blocked from export: it references a Chart of Accounts entry "
                . "rejected by Finance (claims_liability / compensation_expense). "
                . "Confirm replacement accounts with Finance before re-enabling export."
            );
        }

        return null;
    }

    /**
     * Validate all credit note events, flagging any that reference blocked accounts.
     */
    public function validateCreditNotes(
        ?\Carbon\Carbon $from      = null,
        ?\Carbon\Carbon $to        = null,
        ?array          $eventIds  = null
    ): array {
        $query = FinancialEvent::whereIn('event_type', ['credit_note', 'claim', 'spoilage_cool_agristock_fault'])
            ->where('accounting_status', FinancialEvent::STATUS_EXPORT_READY)
            ->where('approval_status',   FinancialEvent::APPROVAL_APPROVED);

        if ($from) $query->where('event_date', '>=', $from->toDateString());
        if ($to)   $query->where('event_date', '<=', $to->toDateString());
        if ($eventIds !== null) $query->whereIn('id', $eventIds);

        $issues = [];

        foreach ($query->get() as $event) {
            $block = $this->checkAccountBlock($event);
            if ($block) {
                $issues[] = $block;
            }

            if (! $event->customer_id) {
                $issues[] = $this->issue('blocking', $event->event_type, $event->id, 'customer_id is missing');
            }

            if ($event->amount <= 0) {
                $issues[] = $this->issue('blocking', $event->event_type, $event->id, 'amount must be greater than zero');
            }
        }

        return $this->summarise($issues);
    }


    /**
     * Validate customers before contact export.
     *
     * @return array{blocking_errors: int, warnings: int, issues: array}
     */
    public function validateContacts(?array $customerIds = null): array
    {
        $query = User::with('group')->whereNull('deleted_at');

        if ($customerIds !== null) {
            $query->whereIn('id', $customerIds);
        }

        $users  = $query->get();
        $issues = [];

        foreach ($users as $user) {
            if (! $user->name) {
                $issues[] = $this->issue('blocking', 'contacts', $user->id, 'name is required for Odoo res.partner import');
            }

            if (! $user->group) {
                $issues[] = $this->issue('warning', 'contacts', $user->id, 'no group assigned — customer_type will default to farmer');
            }
        }

        return $this->summarise($issues);
    }

    /**
     * Validate billings before invoice export.
     *
     * @return array{blocking_errors: int, warnings: int, issues: array}
     */
    public function validateInvoices(
        ?\Carbon\Carbon $from       = null,
        ?\Carbon\Carbon $to         = null,
        ?array          $billingIds = null
    ): array {
        $query = Billing::with(['stock.customer'])->whereNull('deleted_at');

        if ($from) {
            $query->where('billings.created_at', '>=', $from->startOfDay());
        }
        if ($to) {
            $query->where('billings.created_at', '<=', $to->endOfDay());
        }
        if ($billingIds !== null) {
            $query->whereIn('billings.id', $billingIds);
        }

        $billings = $query->get();
        $issues   = [];
        $seen     = [];

        foreach ($billings as $billing) {
            $extId = 'coolagristock.invoice.' . $billing->id;

            // Duplicate external ID guard
            if (in_array($extId, $seen, true)) {
                $issues[] = $this->issue('blocking', 'invoices', $billing->id, "duplicate external ID [{$extId}]");
            }
            $seen[] = $extId;

            $customer = $billing->stock?->customer ?? \App\Models\User::find($billing->customer_id);
            if (! $customer) {
                $issues[] = $this->issue('blocking', 'invoices', $billing->id, 'no customer found — partner_id/id cannot be resolved');
            }

            if ($billing->amount <= 0) {
                $issues[] = $this->issue('blocking', 'invoices', $billing->id, 'amount must be greater than zero');
            }

            // Warning: no tax configured yet
            $issues[] = $this->issue('warning', 'invoices', $billing->id, 'tax_ids/id is empty — VAT must be confirmed before import');
        }

        return $this->summarise($issues);
    }

    /**
     * Validate payments before payment export.
     *
     * @return array{blocking_errors: int, warnings: int, issues: array}
     */
    public function validatePayments(
        ?\Carbon\Carbon $from       = null,
        ?\Carbon\Carbon $to         = null,
        ?array          $paymentIds = null
    ): array {
        $query = Payment::with(['customer', 'billing'])->whereNull('deleted_at');

        if ($from) {
            $query->where('payments.created_at', '>=', $from->startOfDay());
        }
        if ($to) {
            $query->where('payments.created_at', '<=', $to->endOfDay());
        }
        if ($paymentIds !== null) {
            $query->whereIn('payments.id', $paymentIds);
        }

        $payments = $query->get();
        $issues   = [];

        foreach ($payments as $payment) {
            if (! $payment->customer) {
                $issues[] = $this->issue('blocking', 'payments', $payment->id, 'no customer found — partner_id/id cannot be resolved');
            }

            if ($payment->amount <= 0) {
                $issues[] = $this->issue('blocking', 'payments', $payment->id, 'amount must be greater than zero');
            }

            if (! $payment->method) {
                $issues[] = $this->issue('warning', 'payments', $payment->id, 'payment method is missing — journal will default to Cash');
            }

            // If payment references an invoice, check the invoice exists
            if ($payment->billing_id && ! $payment->billing) {
                $issues[] = $this->issue('blocking', 'payments', $payment->id, "referenced billing [{$payment->billing_id}] not found — reconciled_invoice_ids/id will be invalid");
            }
        }

        return $this->summarise($issues);
    }

    // -------------------------------------------------------------------------

    private function issue(string $severity, string $type, int $id, string $message): array
    {
        return compact('severity', 'type', 'id', 'message');
    }

    private function summarise(array $issues): array
    {
        $blockingErrors = count(array_filter($issues, fn ($i) => $i['severity'] === 'blocking'));
        $warnings       = count(array_filter($issues, fn ($i) => $i['severity'] === 'warning'));

        return [
            'blocking_errors' => $blockingErrors,
            'warnings'        => $warnings,
            'issues'          => $issues,
        ];
    }
}
