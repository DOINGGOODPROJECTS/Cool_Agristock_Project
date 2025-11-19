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
 * @property Carbon $login_at
 * @property Carbon $logout_at
 * 
 * @property User $user
 *
 * @package App\Models
 */
class Activity extends Model
{
	public $timestamps = false;
	public $incrementing = false;
	protected $primaryKey = 'id';
	protected $keyType = 'int';

	protected $casts = [
		'user_id' => 'int',
		'login_at' => 'datetime',
		'logout_at' => 'datetime'
	];

	protected $guarded = [];

	protected static function booted(): void
	{
		// Fallback for databases where the id column is not auto-incrementing.
		static::creating(function (Activity $activity) {
			if (is_null($activity->id)) {
				$nextId = (static::max('id') ?? 0) + 1;
				$activity->id = $nextId;
			}
		});
	}

	public function user()
	{
		return $this->belongsTo(User::class);
	}
}
