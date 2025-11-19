<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Claim
 * 
 * @property int $id
 * @property string $name
 * @property string $message
 * @property string $status
 * @property int $customer_id
 * @property int $storage_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property string|null $deleted_at
 * 
 * @property User $user
 * @property Storage $storage
 *
 * @package App\Models
 */
class Claim extends Model
{
	use SoftDeletes;

	protected $casts = [
		'customer_id' => 'int',
		'storage_id' => 'int'
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
}
