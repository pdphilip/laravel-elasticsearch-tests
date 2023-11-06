<?php

namespace App\Models;

use PDPhilip\Elasticsearch\Eloquent\Model as Eloquent;

class PageHit extends Eloquent
{
    protected $connection = 'elasticsearch';
    protected $index = 'page_hits_*';

}
