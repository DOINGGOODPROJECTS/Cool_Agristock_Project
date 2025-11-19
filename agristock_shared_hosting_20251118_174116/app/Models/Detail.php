<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Detail
 * 
 * @property int $id
 * @property float $qty
 * @property int $stock_id
 * @property int $product_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property string|null $deleted_at
 * 
 * @property Product $product
 * @property Stock $stock
 *
 * @package App\Models
 */
class Detail extends Model
{
	protected $casts = [
		'qty' => 'float',
		'stock_id' => 'int',
		'product_id' => 'int'
	];

	protected $guarded = [];

	public function product()
	{
		return $this->belongsTo(Product::class);
	}

	public function stock()
	{
		return $this->belongsTo(Stock::class);
	}

	public function container()
	{
		return $this->belongsTo(Capacity::class, 'container_id');
	}
}
