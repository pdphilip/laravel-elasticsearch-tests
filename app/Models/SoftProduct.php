<?php

namespace App\Models;


use Carbon\Carbon;
use PDPhilip\Elasticsearch\Eloquent\Model;
use PDPhilip\Elasticsearch\Eloquent\SoftDeletes;


/**
 * App\Models\Product
 *
 ******Fields*******
 *
 * @property string $_id
 * @property string $name
 * @property integer $in_stock
 * @property integer $price
 * @property integer $orders
 * @property integer $status
 * @property string $color
 * @property bool $is_active
 * @property array $manufacturer
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 *
 */
class SoftProduct extends Model
{
    
    use SoftDeletes;
    
    protected $connection = 'elasticsearch';
    
    
    protected $fillable = [
        'name', 'in_stock', 'is_active', 'status', 'color', 'manufacturer', 'price', 'orders', 'product_id',
    ];
    
    
}
