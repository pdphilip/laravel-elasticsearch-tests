<?php

namespace App\Models;


use Carbon\Carbon;
use MongoDB\Laravel\Eloquent\Model;
use PDPhilip\Elasticsearch\Eloquent\HybridRelations;

/**
 * App\Models\Client
 *
 ******Fields*******
 *
 * @property string $_id
 * @property string $company_id
 * @property string $name
 * @property integer $status
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 ******Relationships*******
 * @property-read Company $company
 * @property-read ClientLog $clientLogs
 * @property-read ClientProfile $clientProfile
 *
 *
 */
class Client extends Model
{
    use HybridRelations;
    
    protected $connection = 'mongodb';
    
    
    //Relationships  =====================================
    
    public function clientLogs()
    {
        return $this->hasMany(ClientLog::class);
    }
    
    public function clientProfile()
    {
        return $this->hasOne(ClientProfile::class);
    }
    
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    
}
