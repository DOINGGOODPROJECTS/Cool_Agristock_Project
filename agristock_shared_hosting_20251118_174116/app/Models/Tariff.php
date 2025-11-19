<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Tariff
 * 
 * @property int $id
 * @property float $price
 * @property float $min_qty
 * @property float $max_qty
 * @property int $duration
 * @property int $storage_type_id
 * @property int $capacity_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property string|null $deleted_at
 * 
 * @property Container $container
 * @property StorageType $storage_type
 *
 * @package App\Models
 */
class Tariff extends Model
{
	use SoftDeletes;

	protected $casts = [
		'price' => 'float',
		'min_qty' => 'float',
		'max_qty' => 'float',
		'duration' => 'int',
		'storage_type_id' => 'int',
		'capacity_id' => 'int'
	];

	protected $guarded = [];

	public function capacity()
	{
		return $this->belongsTo(Capacity::class, 'container_id');
	}

	public function storage()
	{
		return $this->belongsTo(Storage::class);
	}
}
