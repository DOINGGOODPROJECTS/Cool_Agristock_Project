<?php

namespace App\Services\Accounting;

use App\Models\Claim;
use App\Models\FinancialEvent;
use App\Models\Rotten;
use InvalidArgumentException;
use LogicException;

/**
 * WSC-03/04/05: Handles the claim → credit note → refund workflow.
 *
 * WSC-03: recordClaimEvent()     — creates a financial event when a claim is opened
 * WSC-04: issueCreditNote()      — creates a credit_note financial event from an approved claim
 * WSC-05: requestRefund()        — creates a refund financial event from an approved credit note
 *
 * All events are created through FinancialEventService to enforce
 * idempotency, approval gates, and canonical ID generation.
 */
class ClaimWorkflowService
{
    public function __construct(private readonly FinancialEventService $events) {}

    // -------------------------------------------------------------------------
    // WSC-03: Record a claim event when a claim is submitted
    // -------------------------------------------------------------------------

    /**
     * Record a financial event for an opened/updated claim.
     * The event stays in 'draft' until Finance approves it via FinancialEventService::approve().
     */
    public function recordClaimEvent(Claim $claim, float $claimedAmount, int $createdBy): FinancialEvent
    {
        if ($claimedAmount <= 0) {
            throw new InvalidArgumentException("Claim amount must be greater than zero.");
        }

        return $this->events->record([
            'event_type'       => 'claim',
            'customer_id'      => $claim->customer_id,
            'storage_id'       => $claim->storage_id,
            'amount'           => $claimedAmount,
            'event_date'       => $claim->created_at->toDateString(),
            'source_reference' => (string) $claim->id,
            'source_model'     => 'claims',
            'service_type'     => null,
            'notes'            => $claim->message,
            'created_by'       => $createdBy,
            'idempotency_key'  => 'claim-event-' . $claim->id,
        ]);
    }

    // -------------------------------------------------------------------------
    // WSC-04: Issue a credit note from an approved claim
    // -------------------------------------------------------------------------

    /**
     * Create a credit_note financial event once a claim is approved.
     * The caller must have already called FinancialEventService::approve() on the claim event.
     *
     * @param  FinancialEvent $claimEvent   The approved claim financial event
     * @param  float          $creditAmount The approved credit amount (may differ from claimed)
     * @param  int            $approvedBy
     * @param  string|null    $originalInvoiceRef  billing.id for linking to original invoice
     */
    public function issueCreditNote(
        FinancialEvent $claimEvent,
        float          $creditAmount,
        int            $approvedBy,
        ?string        $originalInvoiceRef = null
    ): FinancialEvent {
        if ($claimEvent->event_type !== 'claim') {
            throw new LogicException("Credit notes can only be issued from approved claim events.");
        }

        if ($claimEvent->approval_status !== FinancialEvent::APPROVAL_APPROVED) {
            throw new LogicException(
                "Claim event [{$claimEvent->financial_event_id}] must be approved before a credit note can be issued."
            );
        }

        if ($creditAmount <= 0) {
            throw new InvalidArgumentException("Credit note amount must be greater than zero.");
        }

        $creditNoteEvent = $this->events->record([
            'event_type'       => 'credit_note',
            'customer_id'      => $claimEvent->customer_id,
            'storage_id'       => $claimEvent->storage_id,
            'amount'           => $creditAmount,
            'event_date'       => now()->toDateString(),
            'source_reference' => $originalInvoiceRef ?? $claimEvent->source_reference,
            'source_model'     => 'claims',
            'notes'            => "Credit note for approved claim {$claimEvent->financial_event_id}",
            'created_by'       => $approvedBy,
            'idempotency_key'  => 'creditnote-from-claim-' . $claimEvent->id,
        ]);

        // Auto-approve — Finance already approved the parent claim
        $this->events->approve($creditNoteEvent, $approvedBy);

        // credit_note is not in the export block list; mark ready
        if (! $this->isExportBlocked('credit_note')) {
            $this->events->markReadyForExport($creditNoteEvent);
        }

        return $creditNoteEvent;
    }

    // -------------------------------------------------------------------------
    // WSC-05: Request a cash refund from an approved credit note event
    // -------------------------------------------------------------------------

    /**
     * Create a refund financial event from an approved credit note.
     * Validates that available credit balance covers the refund amount.
     *
     * @param  FinancialEvent $creditNoteEvent  The approved credit note event
     * @param  float          $refundAmount     Must not exceed the credit note amount
     * @param  string         $paymentChannel   'CASH', 'MOBILE MONEY', 'BANK TRANSFER'
     * @param  int            $approvedBy
     */
    public function requestRefund(
        FinancialEvent $creditNoteEvent,
        float          $refundAmount,
        string         $paymentChannel,
        int            $approvedBy
    ): FinancialEvent {
        if ($creditNoteEvent->event_type !== 'credit_note') {
            throw new LogicException("Refunds can only be issued from approved credit note events.");
        }

        if ($creditNoteEvent->approval_status !== FinancialEvent::APPROVAL_APPROVED) {
            throw new LogicException(
                "Credit note [{$creditNoteEvent->financial_event_id}] must be approved before a refund can be requested."
            );
        }

        if ($refundAmount <= 0) {
            throw new InvalidArgumentException("Refund amount must be greater than zero.");
        }

        if ($refundAmount > $creditNoteEvent->amount) {
            throw new InvalidArgumentException(
                "Refund amount [{$refundAmount}] exceeds approved credit note amount [{$creditNoteEvent->amount}]."
            );
        }

        $refundEvent = $this->events->record([
            'event_type'       => 'refund',
            'customer_id'      => $creditNoteEvent->customer_id,
            'storage_id'       => $creditNoteEvent->storage_id,
            'amount'           => $refundAmount,
            'event_date'       => now()->toDateString(),
            'source_reference' => (string) $creditNoteEvent->id,
            'source_model'     => 'financial_events',
            'service_type'     => strtolower(str_replace(' ', '_', $paymentChannel)),
            'notes'            => "Refund of credit note {$creditNoteEvent->financial_event_id} via {$paymentChannel}",
            'created_by'       => $approvedBy,
            'idempotency_key'  => 'refund-from-creditnote-' . $creditNoteEvent->id,
        ]);

        // Auto-approve and mark ready — Finance approved the credit note
        $this->events->approve($refundEvent, $approvedBy);
        $this->events->markReadyForExport($refundEvent);

        return $refundEvent;
    }

    // -------------------------------------------------------------------------
    // WSC-02: Record spoilage compensation (COOL AGRISTOCK fault)
    // -------------------------------------------------------------------------

    /**
     * Record a spoilage_cool_agristock_fault financial event from a confirmed Rotten record.
     * Requires prior investigation approval — do not call without an approved investigation.
     */
    public function recordSpoilageCompensation(
        Rotten $rotten,
        float  $compensationAmount,
        int    $approvedBy,
        string $investigationNote
    ): FinancialEvent {
        if ($compensationAmount <= 0) {
            throw new InvalidArgumentException("Compensation amount must be greater than zero.");
        }

        $stock = $rotten->stock;

        $event = $this->events->record([
            'event_type'       => 'spoilage_cool_agristock_fault',
            'customer_id'      => $stock->customer_id,
            'stock_id'         => $rotten->stock_id,
            'amount'           => $compensationAmount,
            'event_date'       => $rotten->created_at->toDateString(),
            'source_reference' => (string) $rotten->id,
            'source_model'     => 'rottens',
            'notes'            => $investigationNote,
            'created_by'       => $approvedBy,
            'idempotency_key'  => 'spoilage-cag-fault-' . $rotten->id,
        ]);

        $this->events->approve($event, $approvedBy);

        // spoilage_cool_agristock_fault is blocked from export (rejected accounts)
        // Event is approved and saved but NOT marked export_ready until Finance
        // confirms replacement accounts for claims_liability / compensation_expense.

        return $event;
    }

    // -------------------------------------------------------------------------

    private function isExportBlocked(string $eventType): bool
    {
        return in_array(
            $eventType,
            config('accounting_events.export_blocked_event_types', []),
            true
        );
    }
}
