<?php

namespace Tests\Models;


use Carbon\Carbon;
use PDPhilip\Elasticsearch\Eloquent\Model;


/**
 * App\Models\Post
 *
 ******Fields*******
 *
 * @property string $_id
 * @property string $title
 * @property string $slug
 * @property string $content
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 */
class Post extends Model
{
    
    protected $connection = 'elasticsearch';

//    const MAX_SIZE = 5;
    
    protected $fillable = ['title', 'slug', 'content'];
    
    
}
