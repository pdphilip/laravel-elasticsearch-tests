<?php

namespace Tests\Models;

use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

use PDPhilip\Elasticsearch\Eloquent\Model as Eloquent;

/**
 * App\Models\BlogPost
 *
 ******Fields*******
 *
 * @property string $_id
 * @property string $title
 * @property string $content
 * @property array $comments
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 *
 * @mixin \Eloquent
 *
 */
class BlogPost extends Eloquent
{
    public $connection = 'elasticsearch';
    //----------------------------------------------------------------------
    // Model Definition/Config
    //----------------------------------------------------------------------
    
    
    //----------------------------------------------------------------------
    // Relationships
    //----------------------------------------------------------------------
    
    
    //----------------------------------------------------------------------
    // Attributes
    //----------------------------------------------------------------------
    
    
    //----------------------------------------------------------------------
    // Statics
    //----------------------------------------------------------------------
    
    
    //----------------------------------------------------------------------
    // Entities
    //----------------------------------------------------------------------
    
    
    //----------------------------------------------------------------------
    // Privates/Helpers
    //----------------------------------------------------------------------
    
}



