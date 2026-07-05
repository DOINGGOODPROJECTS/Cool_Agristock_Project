<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'invoice_number', 'billing_id', 'customer_id', 'customer_name', 'invoice_date', 'due_date',
        'stock_lot', 'payment_terms', 'odoo_partner_ref',
        'subtotal', 'tax_amount', 'total_amount', 'currency', 'status',
        'finance_status', 'send_to_odoo', 'odoo_decision_reason', 'accounting_check',
        'odoo_invoice_id', 'odoo_invoice_name', 'odoo_status', 'odoo_push_error',
        'notes', 'generated_by',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date'     => 'date',
        'subtotal'     => 'decimal:2',
        'tax_amount'   => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function billing()
    {
        return $this->belongsTo(Billing::class);
    }

    public function generatedBy()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function lines()
    {
        return $this->hasMany(InvoiceLine::class);
    }

    public function journalEntries()
    {
        return $this->morphMany(JournalEntry::class, 'source');
    }

    public static function nextInvoiceNumber(): string
    {
        $year = now()->year;
        $prefix = "CAG-INV-{$year}-";

        $last = static::withTrashed()
            ->where('invoice_number', 'like', $prefix . '%')
            ->orderByDesc('invoice_number')
            ->value('invoice_number');

        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix . str_pad($next, 5, '0', STR_PAD_LEFT);
    }

    public function getFormattedTotalAttribute(): string
    {
        return number_format((float) $this->total_amount, 0, '.', ' ') . ' XOF';
    }

    public function scopeForCustomer($query, int $userId)
    {
        return $query->where('customer_id', $userId);
    }
}
