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
 * Class Billing
 * 
 * @property int $id
 * @property float $amount
 * @property float $discount
 * @property int $stock_id
 * @property int $client_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property string|null $deleted_at
 * 
 * @property User $user
 * @property Stock $stock
 * @property Collection|Payment[] $payments
 *
 * @package App\Models
 */
class Billing extends Model
{
	use SoftDeletes;

	protected $casts = [
		'amount' => 'float',
		'discount' => 'float',
		'stock_id' => 'int',
		'client_id' => 'int',
		'delayed_at' => 'datetime',
	];

	protected $guarded = [];

	public function customer()
	{
		return $this->belongsTo(User::class, 'customer_id');
	}

	public function stock()
	{
		return $this->belongsTo(Stock::class);
	}

	public function payments()
	{
		return $this->hasMany(Payment::class, 'billing_id');
	}
}
