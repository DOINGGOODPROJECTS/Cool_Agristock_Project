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
 * Class Product
 * 
 * @property int $id
 * @property string $name
 * @property int $category_id
 * @property int $expired_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property string|null $deleted_at
 * 
 * @property Category $category
 * @property Collection|Detail[] $details
 * @property Collection|Rotten[] $rottens
 *
 * @package App\Models
 */
class Product extends Model
{
	use SoftDeletes;

	protected $casts = [
		'category_id' => 'int',
		'expired_at' => 'int'
	];

	protected $guarded = [];

	public function category()
	{
		return $this->belongsTo(Category::class);
	}

	public function details()
	{
		return $this->hasMany(Detail::class);
	}

	public function rottens()
	{
		return $this->hasMany(Rotten::class);
	}
}
