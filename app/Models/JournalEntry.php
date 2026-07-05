<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JournalEntry extends Model
{
    protected $fillable = [
        'reference', 'description', 'entry_date', 'entry_type', 'status',
        'journal_name', 'document_reference', 'event_type', 'provenance_category',
        'source_type', 'source_id', 'financial_event_id',
        'total_debit', 'total_credit', 'currency',
        'odoo_status', 'send_to_odoo', 'odoo_approved_by', 'odoo_approved_at', 'odoo_rejection_reason',
        'odoo_move_id', 'odoo_move_name', 'odoo_push_error',
        'comments',
        'created_by', 'approved_by', 'approved_at', 'posted_by', 'posted_at',
    ];

    protected $casts = [
        'entry_date'      => 'date',
        'approved_at'     => 'datetime',
        'posted_at'       => 'datetime',
        'odoo_approved_at'=> 'datetime',
        'total_debit'     => 'decimal:2',
        'total_credit'    => 'decimal:2',
    ];

    public function lines()
    {
        return $this->hasMany(JournalLine::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function postedBy()
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function odooApprovedBy()
    {
        return $this->belongsTo(User::class, 'odoo_approved_by');
    }

    public function financialEvent()
    {
        return $this->belongsTo(FinancialEvent::class);
    }

    public function source()
    {
        return $this->morphTo();
    }

    public static function nextReference(): string
    {
        $year = now()->year;
        $prefix = "CAG-JNL-{$year}-";

        $last = static::where('reference', 'like', $prefix . '%')
            ->orderByDesc('reference')
            ->value('reference');

        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix . str_pad($next, 5, '0', STR_PAD_LEFT);
    }

    public function isBalanced(): bool
    {
        return bccomp((string) $this->total_debit, (string) $this->total_credit, 2) === 0;
    }

    public function recalculateTotals(): void
    {
        $this->total_debit  = $this->lines()->sum('debit');
        $this->total_credit = $this->lines()->sum('credit');
        $this->save();
    }
}
