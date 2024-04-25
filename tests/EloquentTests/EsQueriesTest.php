<?php

use Tests\Models\Product;
use Tests\Factories\ProductFactory;
use PDPhilip\Elasticsearch\Schema\IndexBlueprint;
use PDPhilip\Elasticsearch\Schema\Schema;


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
    'dash_id'                      => '-test-id',
    'description_test'             => 'This product has "many great" features with yellow/gold covers.',
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
});

it('should create the product index with geo field type', function () {
    Schema::create('products', function (IndexBlueprint $index) {
        $index->geo('manufacturer.location');
    });
    $this->assertTrue(Schema::hasIndex('products'));
});

it('should create products', function () use (&$fieldChecks) {
    $pf = new ProductFactory();
    
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
});


it('should process matrix aggregation', function () use (&$fieldChecks) {
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


it('should find with wherePhrase()', function () use (&$fieldChecks) {
    $found = Product::where('description', 'many great')->get();
    $foundPhrase = Product::wherePhrase('description', '"many great"')->get();
    $this->assertTrue(count($foundPhrase) == 1);
    $this->assertTrue(count($found) > count($foundPhrase));
});

it('should find with whereExact()', function () use (&$fieldChecks) {
    $found = Product::where('description', 'many great')->get();
    $foundPhrase = Product::whereExact('description', 'This product has "many great" features with yellow/gold covers.')->get();
    $this->assertTrue(count($foundPhrase) == 1);
    $this->assertTrue(count($found) > count($foundPhrase));
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


it('should clean up', function () {
    Product::deleteIndexIfExists();
    $this->assertFalse(Schema::hasIndex('products'));
});