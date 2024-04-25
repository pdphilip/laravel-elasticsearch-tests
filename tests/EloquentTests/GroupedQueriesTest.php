<?php

use Tests\Models\Product;
use Tests\Factories\ProductFactory;
use PDPhilip\Elasticsearch\Schema\IndexBlueprint;
use PDPhilip\Elasticsearch\Schema\Schema;


$fieldChecks = [
    'status'                             => [
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
    'actives'                            => 0,
    'orders'                             => [
        '5-20'    => 0,
        '100-200' => 0,
    ],
    'isActiveOrStockGreaterThan50'       => 0,
    'isBlackActiveOrBlueInactive'        => 0,
    'isBlackOrBlueAndStatus1OrNotActive' => 0,
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
        
        if ($i % 10 == 0) {
            
            $product['color'] = 'blue';
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
        }
        if ($i % 10 == 5) {
            $product['color'] = 'red';
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
        if ($product['is_active'] || ($product['in_stock'] > 50)) {
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
        
        if (!empty($product['color'])) {
            if ($product['color'] === 'black' || $product['color'] === 'blue') {
                if ($product['status'] === 1 || !$product['is_active']) {
                    $fieldChecks['isBlackOrBlueAndStatus1OrNotActive']++;
                }
            }
            
        }
        
        
        $i++;
    }
    // Sleep to allow ES to catch up
    sleep(1);
    $this->assertTrue(Schema::hasIndex('products'));
    $this->assertTrue(Product::count() === 100);
});

it('should be able to chunk by ID', function () {
    $prodIds = [];
    Product::chunkById(10, function ($products) use (&$prodIds) {
        foreach ($products as $product) {
            $prodIds[] = $product->_id;
        }
    }, 'product_id.keyword');
    
    $this->assertTrue(count($prodIds) === 100);
});


it('should find active or stock greater than 50', function () use (&$fieldChecks) {
    $prods = Product::whereNested(function ($query) {
        $query->where('is_active', true)->orWhere('in_stock', '>', 50);
    })->get();
    $this->assertTrue(count($prods) === $fieldChecks['isActiveOrStockGreaterThan50']);
    
    $prodsAlt = Product::where(function ($query) {
        $query->where('is_active', true)->orWhere('in_stock', '>', 50);
    })->get();
    $this->assertTrue(count($prodsAlt) === $fieldChecks['isActiveOrStockGreaterThan50']);
    
});

it('should find black and active or blue and inactive', function () use (&$fieldChecks) {
    
    $prodsAlt = Product::where(function ($query) {
        $query->where('color', 'black')->where('is_active', true);
    })->orWhere(
        function ($query) {
            $query->where('color', 'blue')->where('is_active', false);
        }
    )->get();
    $this->assertTrue(count($prodsAlt) === $fieldChecks['isBlackActiveOrBlueInactive']);
    
});


it('should find (Black Or Blue) And (Status1 Or NotActive)', function () use (&$fieldChecks) {
    $prods = Product::whereNested(function ($query) {
        $query->where('color', 'black')->orWhere('color', 'blue');
    })->whereNested(function ($query) {
        $query->where('status', 1)->orWhere('is_active', false);
    }
    )->get();
    $this->assertTrue(count($prods) === $fieldChecks['isBlackOrBlueAndStatus1OrNotActive']);
    
    $prodsAlt = Product::where(function ($query) {
        $query->where('color', 'black')->orWhere('color', 'blue');
    })->where(function ($query) {
        $query->where('status', 1)->orWhere('is_active', false);
    }
    )->get();
    $this->assertTrue(count($prodsAlt) === $fieldChecks['isBlackOrBlueAndStatus1OrNotActive']);
    
});


it('should clean up', function () {
    Product::deleteIndexIfExists();
    $this->assertFalse(Schema::hasIndex('products'));
});