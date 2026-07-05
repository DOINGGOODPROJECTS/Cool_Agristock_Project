<?php

namespace App\Services\Accounting;

use App\Models\FinancialEvent;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;

/**
 * Creates and manages financial events (EVT-01 to EVT-06).
 *
 * All writes are append-only. Call record() to create a new event.
 * Duplicate submissions with the same idempotency_key are silently
 * returned as the existing record (EVT-06).
 */
class FinancialEventService
{
    public function __construct(private readonly AccountingEventRules $rules) {}

    /**
     * Record a new financial event.
     *
     * @param  array{
     *   event_type: string,
     *   customer_id: int,
     *   amount: float,
     *   event_date: string,
     *   source_reference?: string,
     *   source_model?: string,
     *   stock_id?: int,
     *   product_id?: int,
     *   storage_id?: int,
     *   quantity?: float,
     *   unit?: string,
     *   currency?: string,
     *   due_date?: string,
     *   service_type?: string,
     *   tax_id?: string,
     *   notes?: string,
     *   created_by?: int,
     *   idempotency_key?: string,
     * } $data
     */
    public function record(array $data): FinancialEvent
    {
        $eventType = $data['event_type'] ?? null;

        if (! $eventType) {
            throw new InvalidArgumentException('event_type is required.');
        }

        // Validates event type against config (throws on unknown type)
        $this->rules->for($eventType);

        $idempotencyKey = $data['idempotency_key']
            ?? $this->makeIdempotencyKey($eventType, $data);

        // Idempotency guard (EVT-06): return existing record instead of duplicating
        $existing = FinancialEvent::where('idempotency_key', $idempotencyKey)->first();
        if ($existing) {
            return $existing;
        }

        $financialEventId = $this->rules->makeFinancialEventId($eventType);

        return FinancialEvent::create([
            'financial_event_id' => $financialEventId,
            'idempotency_key'    => $idempotencyKey,
            'event_type'         => $eventType,
            'version'            => config('accounting_events.version', '1.0'),
            'source_reference'   => $data['source_reference'] ?? null,
            'source_model'       => $data['source_model'] ?? null,
            'customer_id'        => $data['customer_id'],
            'stock_id'           => $data['stock_id'] ?? null,
            'product_id'         => $data['product_id'] ?? null,
            'storage_id'         => $data['storage_id'] ?? null,
            'quantity'           => $data['quantity'] ?? null,
            'unit'               => $data['unit'] ?? null,
            'amount'             => $data['amount'],
            'currency'           => $data['currency'] ?? config('accounting_events.currency', 'XOF'),
            'event_date'         => $data['event_date'],
            'due_date'           => $data['due_date'] ?? null,
            'service_type'       => $data['service_type'] ?? null,
            'tax_id'             => $data['tax_id'] ?? null,
            'notes'              => $data['notes'] ?? null,
            'accounting_status'  => FinancialEvent::STATUS_DRAFT,
            'created_by'         => $data['created_by'] ?? null,
            'odoo_model'         => $this->rules->odooModel($eventType),
        ]);
    }

    /**
     * Mark a draft event as ready for export once all required fields are present.
     * Events that require approval must be approved before calling this.
     */
    public function markReadyForExport(FinancialEvent $event): void
    {
        // Block event types that reference rejected Chart of Accounts entries
        $blockedTypes = config('accounting_events.export_blocked_event_types', []);
        if (in_array($event->event_type, $blockedTypes, true)) {
            throw new LogicException(
                "Event type [{$event->event_type}] cannot be marked export-ready: it references a "
                . "Chart of Accounts entry rejected by Finance (claims_liability / compensation_expense). "
                . "Update the chart of accounts and remove the block from config/accounting_events.php."
            );
        }

        if ($event->requiresApproval() && $event->approval_status !== FinancialEvent::APPROVAL_APPROVED) {
            throw new LogicException(
                "Financial event [{$event->financial_event_id}] requires finance approval before export."
            );
        }

        $this->validatePayload($event);

        $event->markExportReady();
    }

    /**
     * Record finance approval on an event that requires it.
     */
    public function approve(FinancialEvent $event, int $approvedBy): void
    {
        if (! $this->rules->approvalRequired($event->event_type)) {
            throw new LogicException(
                "Event type [{$event->event_type}] does not require approval."
            );
        }

        $event->update([
            'approval_status' => FinancialEvent::APPROVAL_APPROVED,
            'approved_by'     => $approvedBy,
            'approved_at'     => now(),
        ]);
    }

    /**
     * Reject a pending event with a reason stored in notes.
     */
    public function reject(FinancialEvent $event, int $rejectedBy, string $reason): void
    {
        $event->update([
            'approval_status' => FinancialEvent::APPROVAL_REJECTED,
            'approved_by'     => $rejectedBy,
            'approved_at'     => now(),
            'notes'           => $reason,
        ]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function validatePayload(FinancialEvent $event): void
    {
        $validationRules = $this->rules->validationRules($event->event_type);

        $errors = [];

        if (! $event->customer_id) {
            $errors[] = 'customer_id is required';
        }

        if ($event->amount <= 0 && $this->rules->createsAccountingEntry($event->event_type)) {
            $errors[] = 'amount must be greater than zero';
        }

        if (! $event->event_date) {
            $errors[] = 'event_date is required';
        }

        if (! empty($errors)) {
            throw new InvalidArgumentException(
                "Financial event [{$event->financial_event_id}] failed validation: "
                . implode('; ', $errors)
            );
        }
    }

    private function makeIdempotencyKey(string $eventType, array $data): string
    {
        $parts = [
            $eventType,
            $data['customer_id'] ?? 'no-customer',
            $data['source_reference'] ?? 'no-source',
            $data['event_date'] ?? 'no-date',
            $data['amount'] ?? '0',
        ];

        return hash('sha256', implode('|', $parts));
    }
}
