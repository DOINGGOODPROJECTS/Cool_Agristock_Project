<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\InvalidCastException;
use LogicException;

/**
 * Append-only accounting queue between COOL AGRISTOCK operations and Odoo.
 * Records are never deleted. Corrections go through reversal events.
 *
 * @property int         $id
 * @property string      $financial_event_id   CAG-FE-{EVENT_TYPE}-{uuid}
 * @property string      $idempotency_key
 * @property string      $event_type
 * @property string      $version
 * @property string|null $source_reference
 * @property string|null $source_model
 * @property int         $customer_id
 * @property int|null    $stock_id
 * @property int|null    $product_id
 * @property int|null    $storage_id
 * @property float|null  $quantity
 * @property string|null $unit
 * @property float       $amount
 * @property string      $currency
 * @property Carbon      $event_date
 * @property Carbon|null $due_date
 * @property string|null $service_type
 * @property string|null $tax_id
 * @property string|null $notes
 * @property string      $accounting_status
 * @property string|null $approval_status
 * @property int|null    $approved_by
 * @property Carbon|null $approved_at
 * @property string|null $odoo_model
 * @property string|null $odoo_record_id
 * @property string|null $odoo_reference
 * @property string|null $export_batch_id
 * @property string|null $sync_error_code
 * @property string|null $sync_error_message
 * @property int         $retry_count
 * @property Carbon|null $last_sync_at
 * @property int|null    $created_by
 * @property Carbon      $created_at
 * @property Carbon      $updated_at
 */
class FinancialEvent extends Model
{
    // Controlled accounting lifecycle statuses (EVT-04)
    public const STATUS_DRAFT        = 'draft';
    public const STATUS_EXPORT_READY = 'export_ready';
    public const STATUS_EXPORTED_CSV = 'exported_csv';
    public const STATUS_SYNCED_API   = 'synced_api';
    public const STATUS_RECONCILED   = 'reconciled';
    public const STATUS_FAILED       = 'failed';
    public const STATUS_REVERSED     = 'reversed';

    // Approval statuses
    public const APPROVAL_PENDING  = 'pending';
    public const APPROVAL_APPROVED = 'approved';
    public const APPROVAL_REJECTED = 'rejected';

    // Allowed transitions for the accounting_status state machine (EVT-04)
    private const TRANSITIONS = [
        self::STATUS_DRAFT        => [self::STATUS_EXPORT_READY, self::STATUS_FAILED],
        self::STATUS_EXPORT_READY => [self::STATUS_EXPORTED_CSV, self::STATUS_SYNCED_API, self::STATUS_FAILED],
        self::STATUS_EXPORTED_CSV => [self::STATUS_RECONCILED, self::STATUS_FAILED, self::STATUS_REVERSED],
        self::STATUS_SYNCED_API   => [self::STATUS_RECONCILED, self::STATUS_FAILED, self::STATUS_REVERSED],
        self::STATUS_RECONCILED   => [self::STATUS_REVERSED],
        self::STATUS_FAILED       => [self::STATUS_EXPORT_READY, self::STATUS_REVERSED],
        self::STATUS_REVERSED     => [],
    ];

    protected $guarded = [];

    protected $casts = [
        'quantity'    => 'float',
        'amount'      => 'float',
        'event_date'  => 'date',
        'due_date'    => 'date',
        'approved_at' => 'datetime',
        'last_sync_at'=> 'datetime',
        'retry_count' => 'integer',
        'customer_id' => 'integer',
        'stock_id'    => 'integer',
        'product_id'  => 'integer',
        'storage_id'  => 'integer',
        'approved_by' => 'integer',
        'created_by'  => 'integer',
    ];

    // -------------------------------------------------------------------------
    // State machine (EVT-04)
    // -------------------------------------------------------------------------

    public function transitionTo(string $newStatus): void
    {
        $allowed = self::TRANSITIONS[$this->accounting_status] ?? [];

        if (! in_array($newStatus, $allowed, true)) {
            throw new LogicException(
                "Cannot transition financial event [{$this->financial_event_id}] "
                . "from [{$this->accounting_status}] to [{$newStatus}]."
            );
        }

        $this->accounting_status = $newStatus;
        $this->save();
    }

    public function markExportReady(): void
    {
        $this->transitionTo(self::STATUS_EXPORT_READY);
    }

    public function markExportedCsv(string $batchId): void
    {
        $this->export_batch_id = $batchId;
        $this->save();
        $this->transitionTo(self::STATUS_EXPORTED_CSV);
    }

    public function markSyncedApi(string $odooModel, string $odooRecordId, string $odooReference): void
    {
        $this->odoo_model     = $odooModel;
        $this->odoo_record_id = $odooRecordId;
        $this->odoo_reference = $odooReference;
        $this->last_sync_at   = now();
        $this->save();
        $this->transitionTo(self::STATUS_SYNCED_API);
    }

    public function markReconciled(): void
    {
        $this->transitionTo(self::STATUS_RECONCILED);
    }

    public function markFailed(string $errorCode, string $errorMessage): void
    {
        $this->sync_error_code    = $errorCode;
        $this->sync_error_message = $errorMessage;
        $this->retry_count        = $this->retry_count + 1;
        $this->last_sync_at       = now();
        $this->save();
        $this->transitionTo(self::STATUS_FAILED);
    }

    public function markReversed(): void
    {
        $this->transitionTo(self::STATUS_REVERSED);
    }

    // -------------------------------------------------------------------------
    // Convenience checks
    // -------------------------------------------------------------------------

    public function isPosted(): bool
    {
        return in_array($this->accounting_status, [
            self::STATUS_EXPORTED_CSV,
            self::STATUS_SYNCED_API,
            self::STATUS_RECONCILED,
        ], true);
    }

    public function isEditable(): bool
    {
        return $this->accounting_status === self::STATUS_DRAFT;
    }

    public function requiresApproval(): bool
    {
        return in_array($this->approval_status, [null, self::APPROVAL_PENDING], true)
            && app(\App\Services\Accounting\AccountingEventRules::class)->approvalRequired($this->event_type);
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
