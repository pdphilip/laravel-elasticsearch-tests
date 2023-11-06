<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use PDPhilip\Elasticsearch\Eloquent\HybridRelations;

/**
 * App\Models\User
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection|\Illuminate\Notifications\DatabaseNotification[] $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\Laravel\Sanctum\PersonalAccessToken[] $tokens
 * @property-read int|null $tokens_count
 * @method static \Database\Factories\UserFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User query()
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUpdatedAt($value)
 *
 * *****Relationships*******
 * @property-read User $user
 * @property string $first_name
 * @property string $last_name
 * @property string|null $company_id
 * @property-read \App\Models\Company|null $company
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Photo[] $photos
 * @property-read int|null $photos_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\UserLog[] $userLogs
 * @property-read int|null $user_logs_count
 * @property-read \App\Models\UserProfile|null $userProfile
 * @method static \PDPhilip\Elasticsearch\Eloquent\Builder|User addHybridHas(\Illuminate\Database\Eloquent\Relations\Relation $relation, $operator = '>=', $count = 1, $boolean = 'and', ?\Closure $callback = null)
 * @method static \PDPhilip\Elasticsearch\Eloquent\Builder|User getConnection()
 * @method static \PDPhilip\Elasticsearch\Eloquent\Builder|User raw($expression = null)
 * @method static \PDPhilip\Elasticsearch\Eloquent\Builder|User whereCompanyId($value)
 * @method static \PDPhilip\Elasticsearch\Eloquent\Builder|User whereFirstName($value)
 * @method static \PDPhilip\Elasticsearch\Eloquent\Builder|User whereLastName($value)
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HybridRelations;
    
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
    ];
    
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
    
    public function userLogs()
    {
        return $this->hasMany(UserLog::class);
    }
    
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    
    public function userProfile()
    {
        return $this->hasOne(UserProfile::class);
    }
    
    public function avatar()
    {
        return $this->morphOne(Avatar::class, 'imageable');
    }
    
    public function photos()
    {
        return $this->morphMany(Photo::class, 'photoable');
    }
    
}
