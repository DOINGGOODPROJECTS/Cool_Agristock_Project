<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryOp extends Model
{
    protected $primaryKey = 'op_id';
    protected $keyType    = 'string';
    public    $incrementing = false;
    public    $timestamps   = false;

    protected $guarded = [];

    protected $casts = [
        'client_created_at'  => 'datetime',
        'server_received_at' => 'datetime',
        'applied_at'         => 'datetime',
        'resolved_at'        => 'datetime',
        'cancelled_at'       => 'datetime',
        'quantity_delta'     => 'decimal:3',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function storage()
    {
        return $this->belongsTo(Storage::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function stock()
    {
        return $this->belongsTo(Stock::class);
    }

    public function resolvedBy()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function auditLogs()
    {
        return $this->hasMany(SyncAuditLog::class, 'op_id', 'op_id');
    }
}
