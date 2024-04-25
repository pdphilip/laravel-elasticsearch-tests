<?php

namespace Tests\Models;


use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
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
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 ******Relationships*******
 * @property-read User $user
 *
 *
 * @mixin \Eloquent
 *
 */
class SoftProduct extends Model
{
//    const MAX_SIZE = 5;
    
    use SoftDeletes;
    
    protected $connection = 'elasticsearch';
    
    
    protected $fillable = [
        'name', 'in_stock', 'is_active', 'status', 'color', 'manufacturer', 'price', 'orders', 'product_id',
    ];
    
    
    //Relationships  =====================================
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    
}
