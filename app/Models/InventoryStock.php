<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryStock extends Model
{
    protected $table      = 'inventory_stock';
    protected $guarded    = [];
    public    $timestamps = false;

    protected $casts = [
        'quantity'        => 'decimal:3',
        'last_updated_at' => 'datetime',
    ];

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
}
