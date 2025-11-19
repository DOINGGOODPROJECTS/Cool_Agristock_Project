<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Activity
 * 
 * @property int $id
 * @property int $user_id
 * @property Carbon $logged_at
 * @property Carbon $logout_at
 * 
 * @property User $user
 *
 * @package App\Models
 */
class Activity extends Model
{
	public $timestamps = false;

	protected $casts = [
		'user_id' => 'int',
		'logged_at' => 'datetime',
		'logout_at' => 'datetime'
	];

	protected $guarded = [];

	public function user()
	{
		return $this->belongsTo(User::class);
	}
}
