<?php

namespace App\Models;

use Carbon\Carbon;
use PDPhilip\Elasticsearch\Eloquent\Model;

/**
 * App\Models\UserProfile
 *
 ******Fields*******
 *
 * @property string $_id
 * @property string $user_id
 * @property string $twitter
 * @property string $facebook
 * @property string $address
 * @property string $timezone
 * @property integer $status
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 ******Relationships*******
 * @property-read User $user
 *
 */
class UserProfile extends Model
{
    
    
    protected $connection = 'elasticsearch';
    
    //Relationships  =====================================
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    
}
