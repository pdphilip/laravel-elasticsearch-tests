<?php

namespace App\Models;

use Carbon\Carbon;
use PDPhilip\Elasticsearch\Eloquent\Model;

/**
 * App\Models\ClientProfile
 *
 ******Fields*******
 *
 * @property string $_id
 * @property string $client_id
 * @property string $contact_name
 * @property string $contact_email
 * @property string $website
 * @property integer $status
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 ******Relationships*******
 * @property-read Client $client
 *
 */
class ClientProfile extends Model
{
    
    
    public $connection = 'elasticsearch';
    
    //Relationships  =====================================
    
    public function client()
    {
        return $this->belongsTo(Client::class);
    }
    
    
}
