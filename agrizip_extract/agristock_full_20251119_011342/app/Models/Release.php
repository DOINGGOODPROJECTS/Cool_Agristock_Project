<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Release
 * 
 * @property int $id
 * @property float $before_qty
 * @property float $qty
 * @property float $after_qty
 * @property int $detail_id
 * @property int $stock_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property string|null $deleted_at
 * 
 * @property Detail $detail
 * @property Stock $stock
 *
 * @package App\Models
 */
class Release extends Model
{
	use SoftDeletes;

	protected $casts = [
		'before_qty' => 'float',
		'qty' => 'float',
		'after_qty' => 'float',
		'detail_id' => 'int',
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
