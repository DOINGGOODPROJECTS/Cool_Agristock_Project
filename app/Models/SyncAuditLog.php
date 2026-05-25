<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncAuditLog extends Model
{
    protected $table      = 'sync_audit_log';
    protected $guarded    = [];
    public    $timestamps = false;

    protected $casts = [
        'before_value' => 'array',
        'after_value'  => 'array',
        'created_at'   => 'datetime',
    ];

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function inventoryOp()
    {
        return $this->belongsTo(InventoryOp::class, 'op_id', 'op_id');
    }
}
