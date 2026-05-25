<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MemberPhone extends Model
{
    protected $guarded = [];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
