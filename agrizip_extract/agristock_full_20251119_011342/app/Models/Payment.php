<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Payment
 * 
 * @property int $id
 * @property float $amount
 * @property string $method
 * @property int $bill_id
 * @property int $stock_id
 * @property int $customer_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property string|null $deleted_at
 * 
 * @property Billing $billing
 * @property User $user
 * @property Stock $stock
 *
 * @package App\Models
 */
class Payment extends Model
{
	use SoftDeletes;

	protected $casts = [
		'amount' => 'float',
		'bill_id' => 'int',
		'stock_id' => 'int',
		'customer_id' => 'int'
	];

	protected $guarded = [];

	public function billing()
	{
		return $this->belongsTo(Billing::class, 'billing_id');
	}

	public function user()
	{
		return $this->belongsTo(User::class, 'customer_id');
	}

	public function cashier()
	{
		return $this->belongsTo(User::class, 'created_by');
	}

	public function stock()
	{
		return $this->belongsTo(Stock::class);
	}

	public function customer()
	{
		return $this->belongsTo(User::class);
	}
}
