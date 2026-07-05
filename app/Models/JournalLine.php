<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JournalLine extends Model
{
    protected $fillable = [
        'journal_entry_id',
        'line_date',
        'journal',
        'document_reference',
        'customer_id',
        'customer_name',
        'event_type',
        'provenance_category',
        'description',
        'account_code',
        'account_name',
        'label',
        'debit',
        'credit',
        'currency',
        'send_to_odoo',
        'odoo_status',
        'comments',
    ];

    protected $casts = [
        'line_date' => 'date',
        'debit'  => 'decimal:2',
        'credit' => 'decimal:2',
    ];

    public function entry()
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }

    public function customer()
    {
        return $this->belongsTo(\App\Models\User::class, 'customer_id');
    }
}
