<?php

use Illuminate\Support\Carbon;
use PDPhilip\Elasticsearch\Schema\IndexBlueprint;
use PDPhilip\Elasticsearch\Schema\Schema;
use Tests\Factories\ProductFactory;
use Tests\Models\Product;

$fieldChecks = [
    'status'                        => [
        1 => 0,
        2 => 0,
        3 => 0,
        4 => 0,
        5 => 0,
        6 => 0,
        7 => 0,
        8 => 0,
        9 => 0,
    ],
    'dash_id'                       => '-test-id',
    'description_test'              => 'This product has "many great" features with yellow/gold covers.',
    'actives'                       => 0,
    'orders'                        => [
        '5-20'    => 0,
        '100-200' => 0,
    ],
    'agg_buckets'                   => [
        '0_100'    => 0,
        '100_500'  => 0,
        '500_1000' => 0,
        '1000+'    => 0,
    
    
    ],
    'isActiveAndStockGreaterThan50' => 0,
    'isActiveOrStockGreaterThan50'  => 0,
    'isBlackActiveOrBlueInactive'   => 0,
    'time_checks'                   => [
        'last_order_datetime'  => 0,
        'last_order_ts'        => 0,
        'last_order_ms'        => 0,
        'last_order_gte_ts'    => 0,
        'last_order_gte_ms'    => 0,
        'last_order_gte_count' => 0,
    ],
    'matrix'                        => [
        'count' => 0,
        'max'   => 0,
        'min'   => 100000,
        'sum'   => 0,
    ],
];


it('should remove the existing index if it exists', function () {
    Product::deleteIndexIfExists();
    $this->assertFalse(Schema::hasIndex('products'));
});

it('should create the product index with required field mappings', function () {
    Schema::create('products', function (IndexBlueprint $index) {
        $index->geo('manufacturer.location');
        $index->field('date', 'last_order_datetime', [
            'format' => 'yyyy-MM-dd HH:mm:ss||yyyy-MM-dd',
        ]);
        $index->field('date', 'last_order_ts', [
            'format' => 'epoch_millis||epoch_second',
        ]);
        $index->field('date', 'last_order_ms', [
            'format' => 'epoch_millis||epoch_second',
        ]);
    });
    $this->assertTrue(Schema::hasIndex('products'));
});

it('should create products', function () use (&$fieldChecks) {
    $pf = new ProductFactory();
    $lastWeek = Carbon::now()->subWeek();
    $fieldChecks['time_checks']['last_order_gte_ts'] = $lastWeek->getTimestamp();
    $fieldChecks['time_checks']['last_order_gte_ms'] = $lastWeek->getTimestampMs();
    
    $i = 0;
    while ($i < 100) {
        $product = $pf->definition();
        if ($i === 50) {
            $product['_id'] = $fieldChecks['dash_id'];
        }
        if ($i === 25) {
            $product['description'] = $fieldChecks['description_test'];
        }
        if ($i % 10 == 0) {
            
            $product['color'] = 'blue';
            $product['manufacturer']['country'] = 'Denmark';
            if (!$product['is_active']) {
                $fieldChecks['isBlackActiveOrBlueInactive']++;
            }
        }
        if ($i % 10 == 1) {
            $product['color'] = 'black';
            if ($product['is_active']) {
                $fieldChecks['isBlackActiveOrBlueInactive']++;
            }
        }
        if ($i % 10 == 2) {
            $product['color'] = 'yellow';
            $product['description'] = $product['description'].' great';
        }
        if ($i % 10 == 3) {
            $product['color'] = 'lime';
            $product['description'] = $product['description'].' many';
        }
        if ($i % 10 == 4) {
            $product['color'] = 'green';
            $fieldChecks['matrix']['count']++;
            $fieldChecks['matrix']['max'] = max($fieldChecks['matrix']['max'], $product['orders']);
            $fieldChecks['matrix']['min'] = min($fieldChecks['matrix']['min'], $product['orders']);
            $fieldChecks['matrix']['sum'] += $product['orders'];
        }
        if ($i % 10 == 5) {
            $product['color'] = 'red';
            $fieldChecks['matrix']['count']++;
            $fieldChecks['matrix']['max'] = max($fieldChecks['matrix']['max'], $product['orders']);
            $fieldChecks['matrix']['min'] = min($fieldChecks['matrix']['min'], $product['orders']);
            $fieldChecks['matrix']['sum'] += $product['orders'];
        }
        if ($i % 10 == 6) {
            $product['color'] = 'indigo';
        }
        if ($i % 10 == 7) {
            $product['color'] = '';
        }
        if ($i % 10 == 8) {
            unset($product['color']);
        }
        if ($i % 10 == 9) {
            $product['color'] = null;
        }
        Product::createWithoutRefresh($product);
        //Grab values
        $fieldChecks['status'][$product['status']]++;
        if ($product['is_active'] || ($product['in_stock'] >= 50)) {
            $fieldChecks['isActiveOrStockGreaterThan50']++;
        }
        if ($product['is_active'] && ($product['in_stock'] >= 50)) {
            $fieldChecks['isActiveAndStockGreaterThan50']++;
        }
        if ($product['orders'] >= 5 && $product['orders'] <= 20) {
            $fieldChecks['orders']['5-20']++;
        }
        if ($product['orders'] >= 100 && $product['orders'] <= 200) {
            $fieldChecks['orders']['100-200']++;
        }
        if ($product['is_active']) {
            $fieldChecks['actives']++;
        }
        if ($product['price'] < 100) {
            $fieldChecks['agg_buckets']['0_100']++;
        } elseif ($product['price'] < 500) {
            $fieldChecks['agg_buckets']['100_500']++;
        } elseif ($product['price'] < 1000) {
            $fieldChecks['agg_buckets']['500_1000']++;
        } else {
            $fieldChecks['agg_buckets']['1000+']++;
        }
        $fieldChecks['time_checks']['last_order_datetime'] = $product['last_order_datetime'];
        $fieldChecks['time_checks']['last_order_ts'] = $product['last_order_ts'];
        $fieldChecks['time_checks']['last_order_ms'] = $product['last_order_ms'];
        
        if ($product['last_order_ts'] >= $fieldChecks['time_checks']['last_order_gte_ts']) {
            $fieldChecks['time_checks']['last_order_gte_count']++;
        }
        
        
        $i++;
    }
    // Sleep to allow ES to catch up
    sleep(1);
    $this->assertTrue(Schema::hasIndex('products'));
    $this->assertTrue(Product::count() === 100);
});

//----------------------------------------------------------------------
// Queries  https://elasticsearch.pdphilip.com/querying-models
//----------------------------------------------------------------------

it('should find all products', function () use (&$fieldChecks) {
    $product = Product::all();
    $this->assertTrue(count($product) === 100, 'Not all found. Expecting 100, got '.count($product));
    
    $product = Product::get();
    $this->assertTrue(count($product) === 100, 'Not all found. Expecting 100, got '.count($product));
    
});


//https://elasticsearch.pdphilip.com/querying-models#find
it('should find the first product,find by id and find or fail', function () use (&$fieldChecks) {
    
    $first = Product::first();
    $observedId = $first->_id;
    
    $found = Product::find($observedId);
    $foundOrFailed = Product::findOrFail($observedId);
    $fakeId = $observedId.'xxx';
    $foundFake = Product::find($fakeId);
    
    $this->assertTrue($found->_id === $observedId);
    $this->assertTrue($foundOrFailed->_id === $observedId);
    $this->assertTrue($foundFake === null);
    
    try {
        Product::findOrFail($fakeId);
        $this->assertTrue(false, 'Should have failed');
    } catch (Exception $e) {
        $this->assertTrue(true);
    }
    
});

it('should find by dashed ID', function () use (&$fieldChecks) {
    $product = Product::find($fieldChecks['dash_id']);
    $this->assertTrue($product !== null, 'Product not found with find()');
    
    $products = Product::where('_id', $fieldChecks['dash_id'])->get();
    $this->assertTrue(count($products) === 1, 'Product not found with where()');
    
});

//https://elasticsearch.pdphilip.com/querying-models#where
//https://elasticsearch.pdphilip.com/querying-models#where-not


it('should find all without dashed ID', function () use (&$fieldChecks) {
    
    $products = Product::where('_id', '!=', $fieldChecks['dash_id'])->get();
    $this->assertTrue(count($products) === 99, 'Failed: count was '.count($products));
});

it('should find the product where desc includes slash yellow/gold', function () use (&$fieldChecks) {
    
    $products = Product::where('description', 'yellow/gold')->get();
    
    $this->assertTrue(count($products) === 1, 'Failed: count was '.count($products));
});


it('should find all the yellows', function () {
    $products = Product::where('color', 'yellow')->get();
    
    $this->assertTrue(count($products) === 10);
    
});


it('should find yellows and order by status desc', function () {
    $products = Product::where('color', 'yellow')->orderByDesc('status')->get();
    $statues = [];
    foreach ($products as $product) {
        $statues[] = $product->status;
    }
    $orderedStatuses = $statues;
    arsort($statues);
    
    $this->assertTrue($statues === $orderedStatuses);
});


it('should find a nested data value', function () {
    $found = Product::where('manufacturer.country', 'Denmark')->get();
    
    $this->assertTrue(count($found) >= 10);
});

it('should find with GTE GT LTE LT', function () use (&$fieldChecks) {
    $expectedGTE = $fieldChecks['status'][3] + $fieldChecks['status'][4] + $fieldChecks['status'][5] + $fieldChecks['status'][6] + $fieldChecks['status'][7] + $fieldChecks['status'][8] + $fieldChecks['status'][9];
    $expectedGT = $fieldChecks['status'][4] + $fieldChecks['status'][5] + $fieldChecks['status'][6] + $fieldChecks['status'][7] + $fieldChecks['status'][8] + $fieldChecks['status'][9];
    $expectedLTE = $fieldChecks['status'][1] + $fieldChecks['status'][2] + $fieldChecks['status'][3];
    $expectedLT = $fieldChecks['status'][1] + $fieldChecks['status'][2];
    
    $foundGTE = Product::where('status', '>=', 3)->get();
    $foundGT = Product::where('status', '>', 3)->get();
    $foundLTE = Product::where('status', '<=', 3)->get();
    $foundLT = Product::where('status', '<', 3)->get();
    
    $this->assertTrue(count($foundGTE) === $expectedGTE);
    $this->assertTrue(count($foundGT) === $expectedGT);
    $this->assertTrue(count($foundLTE) === $expectedLTE);
    $this->assertTrue(count($foundLT) === $expectedLT);
});


it('should find not equal to values', function () {
    $test = Product::where('color', '!=', 'lime')->get();
    $this->assertTrue(count($test) === 90);
    
    $testAlt = Product::whereNot('color', 'lime')->get();
    $this->assertTrue(count($testAlt) === 90);
    
});

it('should find all the NOT yellows', function () {
    $products = Product::whereNot('color', 'yellow')->get();
    
    $this->assertTrue(count($products) === 90);
    
});


// https://elasticsearch.pdphilip.com/querying-models#where-using-like
it('should find with LIKE', function () {
    $test = Product::where('color', 'like', 'bl')->orderBy('color.keyword')->get();
    
    $this->assertTrue(count($test) === 20);
});

it('should find with NOT LIKE', function () {
    $test = Product::where('color', 'not like', 'bl')->orderBy('color.keyword')->get();
    
    $this->assertTrue(count($test) === 80);
});


//https://elasticsearch.pdphilip.com/querying-models#and-statements

it('should find AND queries', function () use (&$fieldChecks) {
    $test = Product::where('is_active', true)->where('in_stock', '>=', 50)->get();
    
    $this->assertTrue(count($test) === $fieldChecks['isActiveAndStockGreaterThan50']);
});


//https://elasticsearch.pdphilip.com/querying-models#or-statements
//https://elasticsearch.pdphilip.com/querying-models#chaining-or-and-statements

it('should find OR queries', function () use (&$fieldChecks) {
    $test = Product::where('is_active', true)->orWhere('in_stock', '>=', 50)->get();
    $testWithWhereAndOrs = Product::where('color', 'black')->where('is_active', true)->orWhere('color', 'blue')->where('is_active', false)->get();
    
    $this->assertTrue(count($test) === $fieldChecks['isActiveOrStockGreaterThan50']);
    $this->assertTrue(count($testWithWhereAndOrs) === $fieldChecks['isBlackActiveOrBlueInactive']);
});


it('should find with two OR statements and order correctly - status asc', function () {
    $test = Product::where('color', 'yellow')->orWhere('color', 'green')->orderBy('status')->get();
    $statues = [];
    foreach ($test as $product) {
        if (!in_array($product->color, ['yellow', 'green'])) {
            $this->assertTrue(false); //The wrong color was found
        }
        $statues[] = $product->status;
    }
    $orderedStatuses = $statues;
    asort($statues);
    
    $this->assertTrue($statues === $orderedStatuses);
});


it('should find with two OR statements and order correctly - status desc', function () {
    $test = Product::where('color', 'yellow')->orWhere('color', 'green')->orderBy('status', 'desc')->get();
    $statues = [];
    foreach ($test as $product) {
        if (!in_array($product->color, ['yellow', 'green'])) {
            $this->assertTrue(false); //The wrong color was found
        }
        $statues[] = $product->status;
    }
    $orderedStatuses = $statues;
    arsort($statues);
    
    $this->assertTrue($statues === $orderedStatuses);
});


it('should find with two OR statements and order correctly - date desc', function () {
    $test = Product::where('color', 'yellow')->orWhere('color', 'green')->orderByDesc('created_at')->get();
    $dates = [];
    foreach ($test as $product) {
        if (!in_array($product->color, ['yellow', 'green'])) {
            $this->assertTrue(false); //The wrong color was found
        }
        $dates[] = $product->created_at;
    }
    $orderedDates = $dates;
    arsort($dates);
    
    $this->assertTrue($dates === $orderedDates);
    
});


it('should order correctly on two or more fields', function () {
    $products = Product::orderByDesc('status')->orderBy('orders')->get();
    
    foreach ($products as $i => $product) {
        if ($i > 0) {
            $prev = $products[$i - 1];
            if ($prev->status === $product->status) {
                $this->assertTrue($prev->orders <= $product->orders);
            } else {
                $this->assertTrue($prev->status > $product->status);
            }
        }
    }
    
});


//https://elasticsearch.pdphilip.com/querying-models#where-in
//https://elasticsearch.pdphilip.com/querying-models#where-not-in
it('should find values in an array', function () use (&$fieldChecks) {
    $count = $fieldChecks['status'][1] + $fieldChecks['status'][5];
    $test = Product::whereIn('status', [1, 5, 11])->get();
    $notTest = Product::whereNotIn('color', ['red', 'green'])->get();
    
    $this->assertTrue(count($test) === $count);
    $this->assertTrue(count($notTest) === 80);
});


//https://elasticsearch.pdphilip.com/querying-models#where-null
it('should find by null', function () {
    $testNull = Product::whereNull('color')->get();
    $testNotIn = Product::whereNotIn('color', ['red', 'green', 'blue', 'black', 'yellow', 'indigo', 'lime'])->get();
    $testNotInAll = Product::whereNotIn('color', ['red', 'green', 'blue', 'black', 'yellow', 'indigo', 'lime'])->whereNotNull('color')->get();
    
    $this->assertTrue(count($testNull) === 20);
    $this->assertTrue(count($testNotIn) === 30);
    $this->assertTrue(count($testNotInAll) === 10);
});

//https://elasticsearch.pdphilip.com/querying-models#where-not-null
it('should find by not null', function () {
    $testNotNull = Product::whereNotNull('color')->get();
    
    $this->assertTrue(count($testNotNull) === 80);
});

//https://elasticsearch.pdphilip.com/querying-models#where-between
it('should find values between', function () use (&$fieldChecks) {
    $orders = Product::whereBetween('orders', [5, 20])->get();
    $ordersOr = Product::whereBetween('orders', [5, 20])->orWhereBetween('orders', [100, 200])->get();
    
    $this->assertTrue(count($orders) === $fieldChecks['orders']['5-20']);
    $this->assertTrue(count($ordersOr) === $fieldChecks['orders']['5-20'] + $fieldChecks['orders']['100-200']);
});


//https://elasticsearch.pdphilip.com/querying-models#dates
it('should find by date', function () {
    $test = Product::whereDate('created_at', date('Y-m-d'))->get();
    
    $this->assertTrue(count($test) === 100);
});

//https://elasticsearch.pdphilip.com/querying-models#empty-strings-values
it('should find by empty string', function () {
    $test1 = Product::whereIn('color', [''])->get();
    $test2 = Product::whereExact('color', '')->get();
    
    $this->assertTrue(count($test1) === 10);
    $this->assertTrue(count($test2) === 10);
});


//----------------------------------------------------------------------
// Ordering deep dive: https://elasticsearch.pdphilip.com/ordering-and-pagination
//----------------------------------------------------------------------


//https://elasticsearch.pdphilip.com/ordering-and-pagination#order-by

it('should do basic ordering', function () {
    $ordersAsc = Product::orderBy('orders')->get();
    $ordersDesc = Product::orderBy('orders', 'desc')->get();
    $ordersDescDirect = Product::orderByDesc('orders')->get();
//    dd($ordersAsc[0]->orders, $ordersAsc[1]->orders);
    $foundAscError = false;
    foreach ($ordersAsc as $i => $product) {
        if ($i > 0) {
            if ($product->orders < $ordersAsc[$i - 1]->orders) {
                $foundAscError = true;
            }
        }
    }
    
    $foundDescError = false;
    foreach ($ordersDesc as $i => $product) {
        if ($i > 0) {
            if ($product->orders > $ordersDesc[$i - 1]->orders) {
                $foundDescError = true;
            }
        }
    }
    
    $this->assertFalse($foundAscError);
    $this->assertFalse($foundDescError);
    $this->assertTrue($ordersDesc[40]->_id === $ordersDescDirect[40]->_id);
    
    
});

it('should order by field.keyword', function () {
    $products = Product::orderBy('name.keyword')->get();
    $orderedNames = [];
    foreach ($products as $product) {
        $orderedNames[] = $product->name;
    }
    $testNames = $orderedNames;
    sort($testNames);
    $this->assertTrue($testNames === $orderedNames);
});

//https://elasticsearch.pdphilip.com/ordering-and-pagination#offset-and-limit-skip-and-take
it('should skip and take', function () {
    $first5 = Product::skip(0)->take(5)->get();
    $lastId = $first5[4]->_id;
    $next5 = Product::skip(4)->take(5)->get();
    $this->assertTrue($next5[0]->_id === $lastId);
});

//https://elasticsearch.pdphilip.com/ordering-and-pagination#pagination
it('should paginate', function () use (&$fieldChecks) {
    $products = Product::where('is_active', true);
    $products = $products->paginate(10);
    //$products should be an instance of \Illuminate\Pagination\LengthAwarePaginator
    $this->assertTrue($products->total() === $fieldChecks['actives']);
    
});

//https://elasticsearch.pdphilip.com/ordering-and-pagination#extending-ordering-for-elasticsearch-features
it('should order by color having missing fields first', function () use (&$fieldChecks) {
    $products = Product::orderBy('color.keyword', 'desc', null, '_first')->get();
    
    
    $this->assertTrue($products[0]->color === null);
    $this->assertTrue($products[19]->color === null);
    $this->assertTrue($products[20]->color === 'yellow');
    $this->assertTrue($products[89]->color === 'black');
    $this->assertTrue($products[99]->color === '');
    
});

it('should order by avg order value in desc order', function () use (&$fieldChecks) {
    $products = Product::where('is_active', true)->orderBy('order_values', 'desc', 'avg')->get();
    $currentHighestAvg = 99999999999999;
    $foundError = false;
    foreach ($products as $product) {
        $avg = $product->avg_orders;
        if ($avg > $currentHighestAvg) {
            $foundError = true;
        }
        $currentHighestAvg = $avg;
    }
    $this->assertFalse($foundError);
    
});

//----------------------------------------------------------------------
// Aggregations
//----------------------------------------------------------------------

it('should process basic matrix aggregation', function () use (&$fieldChecks) {
    $check = [
        'count'    => Product::whereIn('color', ['red', 'green'])->count(),
        'max'      => Product::whereIn('color', ['red', 'green'])->max('orders'),
        'min'      => Product::whereIn('color', ['red', 'green'])->min('orders'),
        'avg'      => Product::whereIn('color', ['red', 'green'])->avg('orders'),
        'sum'      => Product::whereIn('color', ['red', 'green'])->sum('orders'),
        'matrix'   => Product::whereIn('color', ['red', 'green'])->matrix(['price', 'orders']),
        'matrix_2' => Product::whereIn('color', ['red', 'green'])->matrix(['orders']),
    ];
    $this->assertTrue($check['count'] == $fieldChecks['matrix']['count']);
    $this->assertTrue($check['max'] == $fieldChecks['matrix']['max']);
    $this->assertTrue($check['min'] == $fieldChecks['matrix']['min']);
    $this->assertTrue($check['sum'] == $fieldChecks['matrix']['sum']);
    $this->assertTrue($check['matrix']['doc_count'] == $fieldChecks['matrix']['count']);
    $this->assertTrue($check['matrix_2']['doc_count'] == $fieldChecks['matrix']['count']);
    $this->assertTrue($check['matrix']['fields'][0]['count'] == $fieldChecks['matrix']['count']);
    $this->assertTrue($check['matrix']['fields'][0]['name'] == 'price');
    $this->assertTrue($check['matrix']['fields'][1]['count'] == $fieldChecks['matrix']['count']);
    $this->assertTrue($check['matrix']['fields'][1]['name'] == 'orders');
    $this->assertTrue($check['matrix_2']['fields'][0]['count'] == $fieldChecks['matrix']['count']);
    
});


//----------------------------------------------------------------------
// Chunking
//----------------------------------------------------------------------


it('should be able to chunk without throwing sortable _id error', function () {
    $prodIds = [];
    Product::chunk(10, function ($products) use (&$prodIds) {
        foreach ($products as $product) {
            $prodIds[] = $product->_id;
        }
    });
    
    $this->assertTrue(count($prodIds) === 100);
});

it('should be able to chunk by Id without throwing sortable _id error', function () {
    $prodIds = [];
    Product::chunkById(10, function ($products) use (&$prodIds) {
        foreach ($products as $product) {
            $prodIds[] = $product->_id;
        }
    });
    
    $this->assertTrue(count($prodIds) === 100);
});

it('should be able to chunk by Id using a given ID field', function () {
    $prodIds = [];
    Product::chunkById(10, function ($products) use (&$prodIds) {
        foreach ($products as $product) {
            $prodIds[] = $product->_id;
        }
    }, 'product_id.keyword');
    
    $this->assertTrue(count($prodIds) === 100);
});


//----------------------------------------------------------------------
// ES Specific
//----------------------------------------------------------------------
//https://elasticsearch.pdphilip.com/es-specific#where-regex

it('should find regex queries', function () use (&$fieldChecks) {
    $found = Product::whereRegex('color', 'bl(ue)?(ack)?')->get();
    $this->assertTrue(count($found) === 20);
    $found = Product::whereRegex('color', 'bl...*')->get();
    $this->assertTrue(count($found) === 20);
});

//https://elasticsearch.pdphilip.com/es-specific#where-phrase
it('should find with wherePhrase()', function () use (&$fieldChecks) {
    $found = Product::where('description', 'many great')->get();
    $foundPhrase = Product::wherePhrase('description', '"many great"')->get();
    $this->assertTrue(count($foundPhrase) == 1);
    $this->assertTrue(count($found) > count($foundPhrase));
});

//https://elasticsearch.pdphilip.com/es-specific#where-exact

it('should find with whereExact()', function () use (&$fieldChecks) {
    $found = Product::where('description', 'many great')->get();
    $foundPhrase = Product::whereExact('description', 'This product has "many great" features with yellow/gold covers.')->get();
    $this->assertTrue(count($foundPhrase) == 1);
    $this->assertTrue(count($found) > count($foundPhrase));
});


//https://elasticsearch.pdphilip.com/es-specific#where-timestamp

it('should find with WhereTimestamp() using seconds', function () use (&$fieldChecks) {
    $found = Product::whereTimestamp('last_order_ts', (int)$fieldChecks['time_checks']['last_order_ts'])->get();
    $this->assertTrue(count($found) === 1);
});

it('should find with WhereTimestamp() using Milliseconds', function () use (&$fieldChecks) {
    $found = Product::whereTimestamp('last_order_ms', (int)$fieldChecks['time_checks']['last_order_ms'])->get();
    $this->assertTrue(count($found) === 1);
});

it('should find GTE with WhereTimestamp() using seconds', function () use (&$fieldChecks) {
    $found = Product::whereTimestamp('last_order_ts', '>=', (int)$fieldChecks['time_checks']['last_order_gte_ts'])->get();
    $this->assertTrue(count($found) === $fieldChecks['time_checks']['last_order_gte_count']);
});


it('should find GTE with WhereTimestamp() using Milliseconds', function () use (&$fieldChecks) {
    $found = Product::whereTimestamp('last_order_ms', '>=', (int)$fieldChecks['time_checks']['last_order_gte_ms'])->get();
    $this->assertTrue(count($found) === $fieldChecks['time_checks']['last_order_gte_count']);
});

//http://localhost:3000/es-specific#raw-dsl-queries
it('should be able to do a raw DSL search', function () {
    $bodyParams = [
        'query' => [
            'match' => [
                'color' => 'lime',
            ],
        ],
    ];
    
    $products = Product::rawSearch($bodyParams);
    foreach ($products as $product) {
        $this->assertTrue($product['color'] === 'lime');
    }
    
});

//http://localhost:3000/es-specific#raw-aggregation-queries

it('should be able to do a raw DSL aggregation', function () use (&$fieldChecks) {
    $bodyParams = [
        'aggs' => [
            'price_ranges' => [
                'range' => [
                    'field'  => 'price',
                    'ranges' => [
                        ['to' => 100],
                        ['from' => 100, 'to' => 500],
                        ['from' => 500, 'to' => 1000],
                        ['from' => 1000],
                    ],
                
                ],
            ],
        ],
    ];
    $priceBuckets = Product::rawAggregation($bodyParams);
    $this->assertTrue(!empty($priceBuckets['price_ranges']));
    $this->assertTrue($priceBuckets['price_ranges'][0]['doc_count'] === $fieldChecks['agg_buckets']['0_100']);
    $this->assertTrue($priceBuckets['price_ranges'][1]['doc_count'] === $fieldChecks['agg_buckets']['100_500']);
    $this->assertTrue($priceBuckets['price_ranges'][2]['doc_count'] === $fieldChecks['agg_buckets']['500_1000']);
    $this->assertTrue($priceBuckets['price_ranges'][3]['doc_count'] === $fieldChecks['agg_buckets']['1000+']);
    
});

//----------------------------------------------------------------------
// Query Meta
//----------------------------------------------------------------------

// https://elasticsearch.pdphilip.com/the-base-model#query-meta

it('should return query meta from model', function () use (&$fieldChecks) {
    $product = Product::where('status', 1)->first();
    $meta = $product->getMeta();
    $this->assertTrue(!empty($meta->_query));
    $this->assertTrue(!empty($meta->_query['shards']));
    $this->assertTrue($meta->_query['total'] === $fieldChecks['status'][1]);
    
});

//----------------------------------------------------------------------
// Error handling
//----------------------------------------------------------------------

//https://elasticsearch.pdphilip.com/handling-errors

it('should catch error and return error details', function () use (&$fieldChecks) {
    $details = [];
    try {
        $product = Product::where('status', 'one')->first();
    } catch (\PDPhilip\Elasticsearch\DSL\exceptions\QueryException $e) {
        $details = $e->getDetails();
    }
    
    $this->assertTrue(!empty($details));
    $this->assertTrue($details['code'] === 400);
    $this->assertTrue($details['exception'] === 'Elastic\Elasticsearch\Exception\ClientResponseException');
    
    
});

//----------------------------------------------------------------------
// Clean up
//----------------------------------------------------------------------

it('should delete them all very quickly', function () {
    $deleted = Product::all();
    $deleted = $deleted->each->delete();
    $this->assertTrue(count($deleted) === 100);
    sleep(1);
    $find = Product::all();
    $this->assertTrue(count($find) === 0);
    
});

it('should return first or create', function () {
    $first = Product::firstOrCreate(['color' => 'blue'], ['status' => 1]);
    $id = $first->_id;
    $this->assertTrue(count(Product::all()) === 1);
    $firstAgain = Product::firstOrCreate(['color' => 'blue'], ['status' => 2]);
    $this->assertTrue(count(Product::all()) === 1);
    $this->assertTrue($firstAgain->_id === $id);
    $this->assertFalse($firstAgain->status === 2);
});


it('should return first or create without refresh', function () {
    $first = Product::firstOrCreateWithoutRefresh(['color' => 'green'], ['status' => 1]);
    $id = $first->_id;
    sleep(2);
    $this->assertTrue(count(Product::all()) === 2);
    $firstAgain = Product::firstOrCreateWithoutRefresh(['color' => 'green'], ['status' => 2]);
    sleep(2);
    $this->assertTrue(count(Product::all()) === 2);
    $this->assertTrue($firstAgain->_id === $id);
    $this->assertFalse($firstAgain->status === 2);
});


it('should clean up', function () {
    Product::deleteIndexIfExists();
    $this->assertFalse(Schema::hasIndex('products'));
});