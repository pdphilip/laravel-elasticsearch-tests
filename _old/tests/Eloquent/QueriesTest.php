<?php

use App\Models\Product;
use Database\Factories\ProductFactory;
use PDPhilip\Elasticsearch\Schema\IndexBlueprint;
use PDPhilip\Elasticsearch\Schema\Schema;

$skip = false;

$fieldChecks = [
    'status'                       => [
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
    'actives'                      => 0,
    'orders'                       => [
        '5-20'    => 0,
        '100-200' => 0,
    ],
    'isActiveOrStockGreaterThan50' => 0,
    'isBlackActiveOrBlueInactive'  => 0,
    'matrix'                       => [
        'count' => 0,
        'max'   => 0,
        'min'   => 100000,
        'sum'   => 0,
    ],
];


it('should remove the existing index if it exists', function () {
    Product::deleteIndexIfExists();
    $this->assertFalse(Schema::hasIndex('products'));
})->skip($skip);

it('should create the product index with geo field type', function () {
    Schema::create('products', function (IndexBlueprint $index) {
        $index->geo('manufacturer.location');
    });
    $this->assertTrue(Schema::hasIndex('products'));
})->skip($skip);

it('should create products', function () use (&$fieldChecks) {
    $pf = new ProductFactory();
    
    $i = 0;
    while ($i < 100) {
        $product = $pf->definition();
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
        }
        if ($i % 10 == 3) {
            $product['color'] = 'lime';
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
            unset($product['color']);
        }
        Product::createWithoutRefresh($product);
        //Grab values
        $fieldChecks['status'][$product['status']]++;
        if ($product['is_active'] || ($product['in_stock'] >= 50)) {
            $fieldChecks['isActiveOrStockGreaterThan50']++;
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
        
        
        $i++;
    }
    // Sleep to allow ES to catch up
    sleep(1);
    $this->assertTrue(Schema::hasIndex('products'));
    $this->assertTrue(Product::count() === 100);
})->skip($skip);


it('should be able to chunk by ID', function () {
    $prodIds = [];
    Product::chunkById(10, function ($products) use (&$prodIds) {
        foreach ($products as $product) {
            $prodIds[] = $product->_id;
        }
    }, 'product_id.keyword');
    
    $this->assertTrue(count($prodIds) === 100);
})->skip($skip);

it('should find all the products', function () {
    $prods = Product::all();
    $this->assertTrue(count($prods) === 100);
})->skip($skip);

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
    
})->skip($skip);

it('should find all the yellows', function () {
    $products = Product::where('color', 'yellow')->get();
    
    $this->assertTrue(count($products) === 10);
    
})->skip($skip);

it('should find yellows and order by status desc', function () {
    $products = Product::where('color', 'yellow')->orderByDesc('status')->get();
    $statues = [];
    foreach ($products as $product) {
        $statues[] = $product->status;
    }
    $orderedStatuses = $statues;
    arsort($statues);
    
    $this->assertTrue($statues === $orderedStatuses);
})->skip($skip);

it('should find a nested data value', function () {
    $found = Product::where('manufacturer.country', 'Denmark')->get();
    
    $this->assertTrue(count($found) >= 10);
})->skip($skip);

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
})->skip($skip);


it('should find not equal to values', function () {
    $test = Product::where('color', '!=', 'lime')->get();
    
    $this->assertTrue(count($test) === 90);
})->skip($skip);

it('should find OR queries', function () use (&$fieldChecks) {
    $test = Product::where('is_active', true)->orWhere('in_stock', '>=', 50)->get();
    $testWithWhereAndOrs = Product::where('color', 'black')->where('is_active', true)->orWhere('color', 'blue')->where('is_active', false)->get();
    
    $this->assertTrue(count($test) === $fieldChecks['isActiveOrStockGreaterThan50']);
    $this->assertTrue(count($testWithWhereAndOrs) === $fieldChecks['isBlackActiveOrBlueInactive']);
})->skip($skip);

it('should find values in an array', function () use (&$fieldChecks) {
    $count = $fieldChecks['status'][1] + $fieldChecks['status'][5];
    $test = Product::whereIn('status', [1, 5, 11])->get();
    $notTest = Product::whereNotIn('color', ['red', 'green'])->get();
    
    $this->assertTrue(count($test) === $count);
    $this->assertTrue(count($notTest) === 80);
})->skip($skip);

it('should find with LIKE', function () {
    $test = Product::where('color', 'like', 'bl')->orderBy('color.keyword')->get();
    
    $this->assertTrue(count($test) === 20);
})->skip($skip);

it('should find by date', function () {
    $test = Product::whereDate('created_at', date('Y-m-d'))->get();
    
    $this->assertTrue(count($test) === 100);
})->skip($skip);

it('should find by null', function () {
    $testNull = Product::whereNull('color')->get();
    $testNotIn = Product::whereNotIn('color', ['red', 'green', 'blue', 'black', 'yellow', 'indigo', 'lime'])->whereNotNull('color')->get();
    
    $this->assertTrue(count($testNull) === 20);
    $this->assertTrue(count($testNotIn) === 10);
})->skip($skip);

it('should find values between', function () use (&$fieldChecks) {
    $orders = Product::whereBetween('orders', [5, 20])->get();
    $ordersOr = Product::whereBetween('orders', [5, 20])->orWhereBetween('orders', [100, 200])->get();
    
    $this->assertTrue(count($orders) === $fieldChecks['orders']['5-20']);
    $this->assertTrue(count($ordersOr) === $fieldChecks['orders']['5-20'] + $fieldChecks['orders']['100-200']);
})->skip($skip);

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
})->skip($skip);

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
})->skip($skip);


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
    
})->skip($skip);

it('should skip and take', function () {
    $first5 = Product::skip(0)->take(5)->get();
    $lastId = $first5[4]->_id;
    $next5 = Product::skip(4)->take(5)->get();
    $this->assertTrue($next5[0]->_id === $lastId);
})->skip($skip);

it('should paginate', function () use (&$fieldChecks) {
    $products = Product::where('is_active', true);
    $products = $products->paginate(10);
    //$products should be an instance of \Illuminate\Pagination\LengthAwarePaginator
    $this->assertTrue($products->total() === $fieldChecks['actives']);
    
})->skip($skip);

describe('ES types', function () use (&$fieldChecks) {
    it('should do some crazy matrix shit', function () use (&$fieldChecks) {
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
    
    it('should find regex queries', function () use (&$fieldChecks) {
        $found = Product::whereRegex('color', 'bl(ue)?(ack)?')->get();
        $this->assertTrue(count($found) === 20);
        $found = Product::whereRegex('color', 'bl...*')->get();
        $this->assertTrue(count($found) === 20);
    });
    
    
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
})->skip($skip);


it('should delete them all very quickly', function () {
    $deleted = Product::all();
    $deleted = $deleted->each->delete();
    $this->assertTrue(count($deleted) === 100);
    sleep(1);
    $find = Product::all();
    $this->assertTrue(count($find) === 0);
    
})->skip($skip);


it('should clean up', function () {
    Product::deleteIndexIfExists();
    $this->assertFalse(Schema::hasIndex('products'));
})->skip($skip);