<?php

namespace Tests\Models;

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
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 */
class Post extends Model
{
    
    protected $connection = 'elasticsearch';

//    const MAX_SIZE = 5;
    
    protected $fillable = ['title', 'slug', 'content'];
    
    
}
