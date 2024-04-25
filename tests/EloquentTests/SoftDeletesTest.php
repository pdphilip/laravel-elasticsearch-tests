<?php

use PDPhilip\Elasticsearch\Schema\IndexBlueprint;
use PDPhilip\Elasticsearch\Schema\Schema;
use Tests\Factories\ProductFactory;
use Tests\Models\SoftProduct;

beforeEach(function () {
    $this->records = 20;
});

$fieldChecks = [
    'status'               => [
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
    'activeWithStatus1or2' => 0,
    'active'               => 0,
];
it('should remove the existing index if it exists', function () {
    SoftProduct::deleteIndexIfExists();
    $this->assertFalse(Schema::hasIndex('soft_products'));
});

it('should create the product index with geo field type', function () {
    Schema::create('soft_products', function (IndexBlueprint $index) {
        $index->geo('manufacturer.location');
    });
    $this->assertTrue(Schema::hasIndex('soft_products'));
});


it('should create Soft Products (which is the same as products but with soft delete)', function () use (&$fieldChecks) {
    
    $pf = new ProductFactory();
    
    $i = 0;
    while ($i < $this->records) {
        $product = $pf->definition();
        SoftProduct::createWithoutRefresh($product);
        $i++;
        $fieldChecks['status'][$product['status']]++;
        if ($product['is_active']) {
            $fieldChecks['active']++;
            if (in_array($product['status'], [1, 2])) {
                $fieldChecks['activeWithStatus1or2']++;
            }
        }
        
        
    }
    // Sleep to allow ES to catch up
    sleep(1);
    $find = SoftProduct::all();
    $this->assertTrue(count($find) === $this->records);
});

it('should soft delete', function () use (&$fieldChecks) {
    
    $all = SoftProduct::whereIn('status', [1, 2])->get();
    $deleted = $all->each->delete();
    // Sleep to allow ES to catch up
    sleep(1);
    
    $shouldBe = $fieldChecks['status'][1] + $fieldChecks['status'][2];
    $is = count($deleted);
    
    $this->assertTrue($is === $shouldBe, "Should be $shouldBe, but is $is");
});

it('should find all except for the soft deleted records', function () use (&$fieldChecks) {
    $find = SoftProduct::all();
    
    $shouldBe = $this->records - $fieldChecks['status'][1] - $fieldChecks['status'][2];
    $is = count($find);
    
    $this->assertTrue($is === $shouldBe, "Should be $shouldBe, but is $is");
});

it('should find deleted records with withTrashed() method', function () use (&$fieldChecks) {
    $find = SoftProduct::withTrashed()->where('status', 1)->get();
    
    $shouldBe = $fieldChecks['status'][1];
    $is = count($find);
    
    $this->assertTrue($is === $shouldBe, "Should be $shouldBe, but is $is");
});

it('should find active records excluding status 1 and 2', function () use (&$fieldChecks) {
    $find = SoftProduct::where('is_active', true)->get();
    
    $shouldBe = $fieldChecks['active'] - $fieldChecks['activeWithStatus1or2'];
    $is = count($find);
    
    $this->assertTrue($is === $shouldBe, "Should be $shouldBe, but is $is");
});

it('should find active records including status 1 via withtrashed', function () use (&$fieldChecks) {
    $find = SoftProduct::withTrashed()->where('is_active', true)->get();
    
    $shouldBe = $fieldChecks['active'];
    $is = count($find);
    
    $this->assertTrue($is === $shouldBe, "Should be $shouldBe, but is $is");
});

it('should be able to restore via the withTrashed() method', function () use (&$fieldChecks) {
    SoftProduct::whereIn('status', [1, 2])->withTrashed()->restore();
    // Sleep to allow ES to catch up
    sleep(1);
    
    $all = SoftProduct::all();
    
    $this->assertTrue(count($all) === $this->records, 'Should be '.$this->records.', but is '.count($all));
});


it('should clean up', function () {
    SoftProduct::deleteIndexIfExists();
    $this->assertFalse(Schema::hasIndex('soft_products'));
});