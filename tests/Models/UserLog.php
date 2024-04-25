<?php

namespace tests\Models;

use App\Casts\EncryptCast;
use Illuminate\Support\Carbon;
use PDPhilip\Elasticsearch\Eloquent\Model;


/**
 * App\Models\UserLog
 *
 ******Fields*******
 *
 * @property string $_id
 * @property string $user_id
 * @property string $company_id
 * @property string $title
 * @property string $score
 * @property string $secret
 * @property integer $code
 * @property mixed $meta
 * @property mixed $agent
 * @property integer $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 ******Relationships*******
 * @property-read User $user
 *
 ******Attributes*******
 * @property-read mixed $status_name
 * @property-read mixed $status_color
 *
 ******scopes*******
 *
 * @method  \PDPhilip\Elasticsearch\Eloquent\Builder|static highScore()
 *
 * @mixin \Eloquent
 *
 */
class UserLog extends Model
{
    
    protected $connection = 'elasticsearch';
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    
    protected $casts = [
        'secret' => EncryptCast::class,
    ];
    
    
    public function getCodeAttribute($value)
    {
        return $value + 1000000;
    }
    
    public function getTitleAttribute($value)
    {
        return 'MR '.ucfirst($value);
    }
    
}
