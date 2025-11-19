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
 * Class City
 * 
 * @property int $id
 * @property string $name
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property string|null $deleted_at
 * 
 * @property Collection|Storage[] $storages
 *
 * @package App\Models
 */
class City extends Model
{
	use SoftDeletes;

	protected $guarded = [];

	public function storages()
	{
		return $this->hasMany(Storage::class);
	}
}
