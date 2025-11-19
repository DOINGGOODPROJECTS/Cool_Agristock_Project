<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Rotten
 * 
 * @property int $id
 * @property float $qty
 * @property int $product_id
 * @property int $stock_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property string|null $deleted_at
 * 
 * @property Product $product
 * @property Stock $stock
 *
 * @package App\Models
 */
class Rotten extends Model
{
	use SoftDeletes;

	protected $casts = [
		'qty' => 'float',
		'product_id' => 'int',
		'stock_id' => 'int'
	];

	protected $guarded = [];

	public function detail()
	{
		return $this->belongsTo(Detail::class);
	}

	public function stock()
	{
		return $this->belongsTo(Stock::class);
	}
}
