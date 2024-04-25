<?php

namespace tests\Models;


use PDPhilip\Elasticsearch\Eloquent\Model as Eloquent;

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
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 ******Relationships*******
 * @property-read Client $client
 *
 ******Attributes*******
 * @property-read mixed $status_name
 * @property-read mixed $status_color
 *
 * @mixin \Eloquent
 *
 */
class ClientLog extends Eloquent
{
    
    
    public $connection = 'elasticsearch';
    
    //Relationships  =====================================
    
    public function client()
    {
        return $this->belongsTo(Client::class);
    }
    
}
