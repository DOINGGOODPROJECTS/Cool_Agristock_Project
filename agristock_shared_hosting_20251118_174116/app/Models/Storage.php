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
 * Class Storage
 * 
 * @property int $id
 * @property string $location
 * @property float $dimension
 * @property float $capacity
 * @property int $city_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property string|null $deleted_at
 * 
 * @property City $city
 * @property Collection|Stock[] $stocks
 * @property Collection|Temperature[] $temperatures
 *
 * @package App\Models
 */
class Storage extends Model
{
	use SoftDeletes;

	protected $casts = [
		'dimension' => 'float',
		'capacity' => 'float',
		'city_id' => 'int'
	];

	protected $guarded = [];

	public function city()
	{
		return $this->belongsTo(City::class);
	}

	public function stocks()
	{
		return $this->hasMany(Stock::class);
	}

	public function temperatures()
	{
		return $this->hasMany(Temperature::class);
	}

	public function available() 
	{
		return $this->capacity - $this->stocks->filter(fn ($stock) => $stock->created_at->addDays($stock->expired_at)->gte(now()))->sum('qty');		
	}
	
	public function tariffs()
	{
		return $this->hasMany(Tariff::class);
	}

	public function claims()
	{
		return $this->hasMany(Claim::class);
	}
}
