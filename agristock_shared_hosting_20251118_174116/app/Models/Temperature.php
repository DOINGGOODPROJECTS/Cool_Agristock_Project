<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Temperature
 * 
 * @property int $id
 * @property float $degree
 * @property int $storage_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property string|null $deleted_at
 * 
 * @property Storage $storage
 *
 * @package App\Models
 */
class Temperature extends Model
{
	use SoftDeletes;

	protected $casts = [
		'degree' => 'float',
		'storage_id' => 'int'
	];

	protected $guarded = [];

	public function storage()
	{
		return $this->belongsTo(Storage::class);
	}
}
