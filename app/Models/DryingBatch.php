<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class DryingBatch
 *
 * Process-level traceability record: which environment/dryer dried which
 * product, for which customer, under which environmental profile, and when.
 *
 * @property int $id
 * @property string $batch_code
 * @property int $storage_id
 * @property int $product_id
 * @property int|null $environmental_profile_id
 * @property int|null $customer_id
 * @property int|null $operator_id
 * @property Carbon $start_time
 * @property Carbon|null $end_time
 * @property string $status
 * @property string|null $outcome
 * @property string|null $notes
 *
 * @property Storage $storage
 * @property Product $product
 * @property EnvironmentalProfile|null $environmentalProfile
 * @property User|null $customer
 * @property User|null $operator
 *
 * @package App\Models
 */
class DryingBatch extends Model
{
    use SoftDeletes;

    protected $casts = [
        'storage_id' => 'int',
        'product_id' => 'int',
        'environmental_profile_id' => 'int',
        'customer_id' => 'int',
        'operator_id' => 'int',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    protected $guarded = [];

    public function storage()
    {
        return $this->belongsTo(Storage::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function environmentalProfile()
    {
        return $this->belongsTo(EnvironmentalProfile::class);
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function operator()
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'in_progress';
    }
}
