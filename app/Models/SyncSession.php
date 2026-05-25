<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncSession extends Model
{
    protected $primaryKey = 'session_id';
    protected $keyType    = 'string';
    public    $incrementing = false;

    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
