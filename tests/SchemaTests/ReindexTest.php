<?php

use Illuminate\Support\Facades\DB;
use PDPhilip\Elasticsearch\Schema\IndexBlueprint;
use PDPhilip\Elasticsearch\Schema\Schema;
use Tests\Factories\ProductFactory;
use Tests\Models\Product;

beforeEach(function () {
    $this->prefix = DB::connection('elasticsearch')->getConfig('index_prefix');
});
it('should be able to create a products index and add 100 products', function () {
    Schema::deleteIfExists('products');
    $products = Schema::create('products', function (IndexBlueprint $index) {
        $index->text('name');
        $index->float('price');
        $index->integer('status');
        $index->date('created_at');
        $index->date('updated_at');
    });
    
    $this->assertTrue(!empty($products[$this->prefix.'_products']['mappings']));
    $this->assertTrue(!empty($products[$this->prefix.'_products']['settings']));
    $pf = new ProductFactory();
    
    $i = 0;
    while ($i < 100) {
        $product = $pf->definition();
        Product::createWithoutRefresh($product);
        $i++;
    };
    sleep(1);
    $find = Product::all();
    $this->assertTrue(count($find) === 100);
});

it('should not be able to find with geo location', function () {
    try {
        $found = Product::filterGeoPoint('manufacturer.location', '10000km', [0, 0])->get();
        $this->assertTrue(false);
    } catch (Exception $e) {
        $this->assertTrue(true);
    }
});

it('should create a new holding index for products with geo', function () {
    $products = Schema::create('holding_products', function (IndexBlueprint $index) {
        $index->text('name');
        $index->float('price');
        $index->integer('status');
        $index->geo('manufacturer.location');
        $index->date('created_at');
        $index->date('updated_at');
    });
    $this->assertTrue($products[$this->prefix.'_holding_products']['mappings']['properties']['manufacturer']['properties']['location']['type'] == 'geo_point');
    $this->assertTrue(!empty($products[$this->prefix.'_holding_products']['settings']));
});

it('should be able to re-index', function () {
    $reindex = Schema::reIndex('products', 'holding_products');
    $this->assertTrue($reindex->data['created'] == 100);
    sleep(2);
    $findOld = DB::connection('elasticsearch')->table($this->prefix.'_products')->count();
    $findNew = DB::connection('elasticsearch')->table($this->prefix.'_holding_products')->count();
    
    $this->assertTrue($findOld === 100, 'Old index count is not 100, it is '.$findOld);
    $this->assertTrue($findNew === 100, 'New index count is not 100, it is '.$findNew);
});

it('should delete original index', function () {
    Schema::delete('products');
    $this->assertFalse(Schema::hasIndex('products'));
    
});

it('should re-create the original index properly', function () {
    $products = Schema::create('products', function (IndexBlueprint $index) {
        $index->text('name');
        $index->float('price');
        $index->integer('status');
        $index->geo('manufacturer.location');
        $index->date('created_at');
        $index->date('updated_at');
    });
    $this->assertTrue(!empty($products[$this->prefix.'_products']['mappings']));
    $this->assertTrue(!empty($products[$this->prefix.'_products']['settings']));
});

it('should re-index back to the original model', function () {
    $reindex = Schema::reIndex('holding_products', 'products');
    $this->assertTrue($reindex->data['created'] == 100);
    //Sleep to allow ES to catch up
    sleep(2);
    $countOriginal = DB::connection('elasticsearch')->table($this->prefix.'_products')->count();
    $countHolding = DB::connection('elasticsearch')->table($this->prefix.'_holding_products')->count();
    
    $this->assertTrue($countOriginal === 100, 'Original index count is not 100, it is '.$countOriginal);
    $this->assertTrue($countHolding === 100, 'Holding index count is not 100, it is '.$countHolding);
});

it('should delete the temporary index', function () {
    Schema::delete('holding_products');
    $this->assertFalse(Schema::hasIndex('holding_products'));
});

it('should now be able to find with geo location', function () {
    try {
        $found = Product::filterGeoPoint('manufacturer.location', '10000km', [0, 0])->get();
        $this->assertTrue(true);
    } catch (Exception $e) {
        $this->assertTrue(false, 'It should have been able to find with geo location');
    }
});

it('should clean up test indexes', function () {
    Schema::deleteIfExists('products');
    Schema::deleteIfExists('holding_products');
    $this->assertFalse(Schema::hasIndex('products'));
    $this->assertFalse(Schema::hasIndex('holding_products'));
});