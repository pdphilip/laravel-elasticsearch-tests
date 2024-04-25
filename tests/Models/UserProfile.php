<?php

namespace Tests\Models;

use Carbon\Carbon;
use PDPhilip\Elasticsearch\Eloquent\Model as Eloquent;

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
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 ******Relationships*******
 * @property-read User $user
 *
 ******Attributes*******
 * @property-read mixed $status_name
 * @property-read mixed $status_color
 *
 * @mixin \Eloquent
 *
 */
class UserProfile extends Eloquent
{
    
    
    protected $connection = 'elasticsearch';
    
    //Relationships  =====================================
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    
}
