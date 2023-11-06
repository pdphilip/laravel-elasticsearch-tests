<?php

namespace App\Models;

use Carbon\Carbon;
use PDPhilip\Elasticsearch\Eloquent\Model;

/**
 * App\Models\CompanyProfile
 *
 ******Fields*******
 *
 * @property string $_id
 * @property string $company_id
 * @property string $address
 * @property string $website
 * @property integer $status
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 ******Relationships*******
 * @property-read Company $company
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
