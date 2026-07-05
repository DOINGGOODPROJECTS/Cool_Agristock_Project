<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int         $id
 * @property string      $batch_id
 * @property string      $export_type
 * @property string      $status
 * @property string|null $file_path
 * @property string|null $file_name
 * @property string|null $file_checksum
 * @property int         $row_count
 * @property int|null    $file_size_bytes
 * @property Carbon|null $period_from
 * @property Carbon|null $period_to
 * @property array|null  $filters
 * @property int         $validation_errors
 * @property string|null $validation_report
 * @property string|null $error_message
 * @property int|null    $generated_by
 * @property Carbon|null $generated_at
 * @property Carbon|null $downloaded_at
 */
class ExportBatch extends Model
{
    public const STATUS_PENDING    = 'pending';
    public const STATUS_GENERATING = 'generating';
    public const STATUS_READY      = 'ready';
    public const STATUS_DOWNLOADED = 'downloaded';
    public const STATUS_FAILED     = 'failed';

    public const TYPE_CONTACTS     = 'contacts';
    public const TYPE_INVOICES     = 'invoices';
    public const TYPE_PAYMENTS     = 'payments';
    public const TYPE_CREDIT_NOTES = 'credit_notes';
    public const TYPE_REFUNDS      = 'refunds';
    public const TYPE_ZIP          = 'zip_package';

    protected $guarded = [];

    protected $casts = [
        'filters'        => 'array',
        'period_from'    => 'date',
        'period_to'      => 'date',
        'generated_at'   => 'datetime',
        'downloaded_at'  => 'datetime',
        'row_count'      => 'integer',
        'file_size_bytes'=> 'integer',
        'validation_errors' => 'integer',
        'generated_by'   => 'integer',
    ];

    public function generator()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function isReady(): bool
    {
        return $this->status === self::STATUS_READY;
    }
}
