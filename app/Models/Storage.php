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
		'city_id' => 'int',
		'stale_threshold_minutes' => 'int'
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

	public function inventoryOps()
	{
		return $this->hasMany(InventoryOp::class);
	}

	public function dryingBatches()
	{
		return $this->hasMany(DryingBatch::class);
	}

	public function activeBatch()
	{
		return $this->hasOne(DryingBatch::class)->where('status', 'in_progress')->latestOfMany('start_time');
	}

	public function isSensorEnabled(): bool
	{
		return !empty($this->thingsboard_device_id);
	}

	/**
	 * Scope environments to what a user is allowed to see in Smart Sensors.
	 * Admin/Supervisor (group 1/2) see every sensor-enabled environment.
	 * Everyone else only sees environments tied to a drying batch they own.
	 */
	public function scopeVisibleTo($query, User $user)
	{
		$query->whereNotNull('thingsboard_device_id');

		if (!in_array($user->group_id, [1, 2])) {
			$query->whereHas('dryingBatches', fn ($q) => $q->where('customer_id', $user->id));
		}

		return $query;
	}
}
