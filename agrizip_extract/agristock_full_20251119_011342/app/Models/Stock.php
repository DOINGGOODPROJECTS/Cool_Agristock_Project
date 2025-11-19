<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Stock
 * 
 * @property int $id
 * @property float $space
 * @property int $duration
 * @property float $qty
 * @property int $storage_id
 * @property int $customer_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property string|null $deleted_at
 * 
 * @property User $user
 * @property Storage $storage
 * @property Collection|Billing[] $billings
 * @property Collection|Detail[] $details
 * @property Collection|Payment[] $payments
 * @property Collection|Rotten[] $rottens
 *
 * @package App\Models
 */
class Stock extends Model
{
	use SoftDeletes;

	protected $casts = [
		'space' => 'float',
		'duration' => 'int',
		'qty' => 'float',
		'storage_id' => 'int',
		'customer_id' => 'int'
	];

	protected $guarded = [];

	public function customer()
	{
		return $this->belongsTo(User::class, 'customer_id');
	}

	public function storage()
	{
		return $this->belongsTo(Storage::class);
	}

	public function billing()
	{
		return $this->hasOne(Billing::class);
	}

	public function details()
	{
		return $this->hasMany(Detail::class);
	}

	public function payments()
	{
		return $this->hasMany(Payment::class);
	}

	public function rottens()
	{
		return $this->hasMany(Rotten::class);
	}

	public function releases()
	{
		return $this->hasMany(Release::class);
	}

	public function supervisor()
	{
		return $this->belongsTo(User::class, 'created_by');
	}
}
