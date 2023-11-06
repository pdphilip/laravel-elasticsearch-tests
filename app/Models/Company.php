<?php

namespace App\Models;

use Carbon\Carbon;
use PDPhilip\Elasticsearch\Eloquent\Model as Eloquent;

/**
 * App\Models\Company
 *
 ******Fields*******
 *
 * @property string $_id
 * @property string $name
 * @property integer $status
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 ******Relationships*******
 * @property-read User $users
 * @property-read UserLog $userLogs
 * @property-read CompanyProfile $companyProfile
 * @property-read Avatar $avatar
 * @property-read Photo $photos
 * @property-read Client $clients
 *
 *
 */
class Company extends Eloquent
{
    
    protected $connection = 'elasticsearch';
    
    
    //Relationships  =====================================
    
    public function users()
    {
        return $this->hasMany(User::class);
    }
    
    public function userLogs()
    {
        return $this->hasMany(UserLog::class);
    }
    
    public function companyProfile()
    {
        return $this->hasOne(CompanyProfile::class);
    }
    
    public function avatar()
    {
        return $this->morphOne(Avatar::class, 'imageable');
    }
    
    public function photos()
    {
        return $this->morphMany(Photo::class, 'photoable');
    }
    
    
    public function clients()
    {
        return $this->hasMany(Client::class);
    }
    
    
}
