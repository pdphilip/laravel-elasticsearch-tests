<?php

use App\Models\SoftProduct;
use Database\Factories\ProductFactory;
use PDPhilip\Elasticsearch\Schema\IndexBlueprint;
use PDPhilip\Elasticsearch\Schema\Schema;

$skip = false;

$fieldChecks = [
    'status' => [
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
];
it('should remove the existing index if it exists', function () {
    SoftProduct::deleteIndexIfExists();
    $this->assertFalse(Schema::hasIndex('soft_products'));
})->skip($skip);

it('should create the product index with geo field type', function () {
    Schema::create('soft_products', function (IndexBlueprint $index) {
        $index->geo('manufacturer.location');
    });
    $this->assertTrue(Schema::hasIndex('soft_products'));
})->skip($skip);


it('should create Soft Products (which is the same as products but with soft delete)', function () use (&$fieldChecks) {
    
    $pf = new ProductFactory();
    
    $i = 0;
    while ($i < 100) {
        $product = $pf->definition();
        SoftProduct::createWithoutRefresh($product);
        $i++;
        $fieldChecks['status'][$product['status']]++;
    }
    // Sleep to allow ES to catch up
    sleep(1);
    $find = SoftProduct::all();
    $this->assertTrue(count($find) === 100);
})->skip($skip);

it('should soft delete', function () use (&$fieldChecks) {
    
    $all = SoftProduct::whereIn('status', [1, 2])->get();
    $deleted = $all->each->delete();
    // Sleep to allow ES to catch up
    sleep(1);
    
    $shouldBe = $fieldChecks['status'][1] + $fieldChecks['status'][2];
    $is = count($deleted);
    
    $this->assertTrue($is === $shouldBe, "Should be $shouldBe, but is $is");
})->skip($skip);

it('should find all except for the soft deleted records', function () use (&$fieldChecks) {
    $find = SoftProduct::all();
    
    $shouldBe = 100 - $fieldChecks['status'][1] - $fieldChecks['status'][2];
    $is = count($find);
    
    $this->assertTrue($is === $shouldBe, "Should be $shouldBe, but is $is");
})->skip($skip);

it('should find deleted records with withTrashed() method', function () use (&$fieldChecks) {
    $find = SoftProduct::withTrashed()->where('status', 1)->get();
    
    $shouldBe = $fieldChecks['status'][1];
    $is = count($find);
    
    $this->assertTrue($is === $shouldBe, "Should be $shouldBe, but is $is");
})->skip($skip);

it('should be able to restore via the withTrashed() method', function () use (&$fieldChecks) {
    SoftProduct::whereIn('status', [1, 2])->withTrashed()->restore();
    // Sleep to allow ES to catch up
    sleep(1);
    
    $all = SoftProduct::all();
    
    $this->assertTrue(count($all) === 100, 'Should be 100, but is '.count($all));
})->skip($skip);


it('should clean up', function () {
    SoftProduct::deleteIndexIfExists();
    $this->assertFalse(Schema::hasIndex('soft_products'));
})->skip($skip);