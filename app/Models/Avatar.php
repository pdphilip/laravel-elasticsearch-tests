<?php

namespace App\Models;

use Carbon\Carbon;
use PDPhilip\Elasticsearch\Eloquent\Model;

/**
 * App\Models\Avatar
 *
 ******Fields*******
 *
 * @property string $_id
 * @property string $url
 * @property string $imageable_id
 * @property string $imageable_type
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 ******Relationships*******
 * @property-read User $user
 *
 *
 */
class Avatar extends Model
{
    protected $connection = 'elasticsearch';
    
    //Relationships  =====================================
    
    public function imageable()
    {
        return $this->morphTo();
    }
    
    
}
