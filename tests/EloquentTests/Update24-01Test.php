<?php

use Tests\Models\Product;
use Tests\Factories\ProductFactory;
use PDPhilip\Elasticsearch\Schema\IndexBlueprint;
use PDPhilip\Elasticsearch\Schema\Schema;
use Illuminate\Support\Carbon;

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
    'description_test_two'          => 'Can’t stop buying plants? Unbeleafable',
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
        if ($i === 26) {
            $product['description'] = $fieldChecks['description_test_two'];
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
// Phrase prefix
//----------------------------------------------------------------------

it('should find wherePhrasePrefix()', function () use (&$fieldChecks) {
    $find = Product::wherePhrasePrefix('description', 'This product h')->get();
    $this->assertTrue(count($find) === 1);
});

//----------------------------------------------------------------------
// Search phrase (multiple sequential words)
//----------------------------------------------------------------------`

it('should search for phase', function () use (&$fieldChecks) {
    $find = Product::phrase('This product has')->search();
    $this->assertTrue(count($find) === 1);
});

it('should search for phase and phrase', function () use (&$fieldChecks) {
    $find = Product::phrase('This product has')->andPhrase('features with')->search();
    $this->assertTrue(count($find) === 1);
});

it('should search for phase or phrase', function () use (&$fieldChecks) {
    $find = Product::phrase('This product has')->orPhrase('stop buying plants')->search();
    $this->assertTrue(count($find) === 2);
});

//----------------------------------------------------------------------
// to DSL/SQL
//----------------------------------------------------------------------

it('should convert to DSL', function () use (&$fieldChecks) {
    $dsl = Product::where('color', 'black')->toDsl();
    $this->assertTrue($dsl['body']['query'] === [
            'match' => [
                'color' => 'black',
            ],
        ]);
    $sql = Product::where('color', 'black')->toSql();
    $this->assertTrue($dsl === $sql);
});

//----------------------------------------------------------------------
// Multiple Aggregations
//----------------------------------------------------------------------

it('should do multiple aggs at once', function () use (&$fieldChecks) {
    $aggs = Product::whereIn('color', ['red', 'green'])->agg(['count', 'max', 'min', 'sum'], 'orders');
    
    $this->assertTrue($aggs['min_orders']['value'] == $fieldChecks['matrix']['min']);
    $this->assertTrue($aggs['count_orders']['value'] == $fieldChecks['matrix']['count']);
    $this->assertTrue($aggs['sum_orders']['value'] == $fieldChecks['matrix']['sum']);
    $this->assertTrue($aggs['max_orders']['value'] == $fieldChecks['matrix']['max']);
    
});


it('should clean up', function () {
    Product::deleteIndexIfExists();
    $this->assertFalse(Schema::hasIndex('products'));
});