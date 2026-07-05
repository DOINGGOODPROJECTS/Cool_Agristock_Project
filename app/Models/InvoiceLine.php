<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceLine extends Model
{
    protected $fillable = [
        'invoice_id',
        'line_no',
        'service',
        'category',
        'product',
        'description',
        'unit',
        'quantity',
        'unit_price',
        'discount_fixed_amount',
        'amount_before_vat',
        'vat_rate',
        'vat_amount',
        'total_amount',
        'journal_entry_no',
        'send_to_odoo',
        'odoo_decision_reason',
        'comments',
    ];

    protected $casts = [
        'quantity'              => 'decimal:4',
        'unit_price'            => 'decimal:2',
        'discount_fixed_amount' => 'decimal:2',
        'amount_before_vat'     => 'decimal:2',
        'vat_rate'              => 'decimal:4',
        'vat_amount'            => 'decimal:2',
        'total_amount'          => 'decimal:2',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
