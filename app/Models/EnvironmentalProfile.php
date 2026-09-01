<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class EnvironmentalProfile
 *
 * Target environmental range (temperature/RH/airflow) for a product,
 * used by the Smart Sensor Management module to evaluate live telemetry.
 *
 * @property int $id
 * @property int $product_id
 * @property float|null $min_temperature
 * @property float|null $max_temperature
 * @property float|null $min_rh
 * @property float|null $max_rh
 * @property float|null $min_airflow
 *
 * @property Product $product
 *
 * @package App\Models
 */
class EnvironmentalProfile extends Model
{
    use SoftDeletes;

    protected $casts = [
        'product_id' => 'int',
        'min_temperature' => 'float',
        'max_temperature' => 'float',
        'min_rh' => 'float',
        'max_rh' => 'float',
        'min_airflow' => 'float',
    ];

    protected $guarded = [];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function dryingBatches()
    {
        return $this->hasMany(DryingBatch::class);
    }
}
