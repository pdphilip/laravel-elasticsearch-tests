<?php

namespace App\Models;

use Carbon\Carbon;
use PDPhilip\Elasticsearch\Eloquent\Model;

/**
 * App\Models\ClientLog
 *
 ******Fields*******
 *
 * @property string $_id
 * @property string $client_id
 * @property string $title
 * @property string $desc
 * @property integer $status
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 ******Relationships*******
 * @property-read Client $client
 *
 ******Attributes*******
 * @property-read mixed $status_name
 * @property-read mixed $status_color
 *
 *
 */
class ClientLog extends Model
{
    
    
    public $connection = 'elasticsearch';
    
    //Relationships  =====================================
    
    public function client()
    {
        return $this->belongsTo(Client::class);
    }
    
}
