<?php

namespace Tests\Models;

use PDPhilip\Elasticsearch\Eloquent\Model;

/**
 * App\Models\
 *
 ******Fields*******
 *
 * @property string $_id
 * @property string $company_id
 * @property string $address
 * @property string $website
 * @property integer $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 ******Relationships*******
 * @property-read Company $company
 *
 ******Attributes*******
 *
 * @mixin \Eloquent
 *
 */
class CompanyProfile extends Model
{
    protected $connection = 'elasticsearch';
    
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    
}
