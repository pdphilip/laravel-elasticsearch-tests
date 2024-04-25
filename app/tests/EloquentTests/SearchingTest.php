<?php

use PDPhilip\Elasticsearch\Schema\IndexBlueprint;
use PDPhilip\Elasticsearch\Schema\Schema;
use tests\Factories\ProductFactory;
use tests\Models\Product;

$fieldChecks = [
    'hasUSA'     => 0,
    'hasUorSorA' => 0,

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
    while ($i < 1000) {
        $product = $pf->definition();
        if ($i % 10 == 0) {
            $product['color'] = 'blue';
            
        }
        if ($i % 10 == 1) {
            $product['color'] = 'black';
            
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
            $product['color'] = 'silver';
        }
        if ($i % 10 == 8) {
            $product['color'] = '';
        }
        if ($i % 10 == 9) {
            unset($product['color']);
        }
        Product::createWithoutRefresh($product);
        $i++;
    }
    // Sleep to allow ES to catch up
    sleep(2);
    $this->assertTrue(Schema::hasIndex('products'));
    $this->assertTrue(Product::count() === 1000, 'Count is '.Product::count());
});


it('can search for a term', function () use (&$fieldChecks) {
    $set = Product::term('United States America')->field('manufacturer.country')->search();
    $set2 = Product::term('United')->orTerm('States')->orTerm('America')->field('manufacturer.country')->search();
    
    $found = count($set);
    
    $this->assertTrue($found > 0);
    $this->assertTrue(count($set2) == $found);
    
});


it('should find terms where minShouldMatch()', function () {
    $records = Product::term('United States America')->field('manufacturer.country')->minShouldMatch(3)->search();
    foreach ($records as $record) {
        $this->assertTrue($record->manufacturer['country'] == 'United States of America');
    }
    
    $records = Product::term('United States America')->fields(['manufacturer.country', 'manufacturer.owned_by.country'])->minShouldMatch(3)->search();
    foreach ($records as $record) {
        $this->assertTrue($record->manufacturer['country'] == 'United States of America' || $record->manufacturer['owned_by']['country'] == 'United States of America');
    }
});

it('should find with minScore()', function () {
    $search1 = Product::term('United States of')->andTerm('America')->minScore(10)->search();
    $search2 = Product::term('United States of')->andTerm('America')->minScore(1000)->search();
    
    $this->assertTrue(count($search1) > 0);
    $this->assertTrue(count($search2) == 0);
});

it('should work combined with where clauses', function () {
    $find = Product::where('color', 'black')->first();
    $name = $find->name;
    $search1 = Product::term($name)->search();
    $search2 = Product::term($name)->where('color', 'black')->search();
    
    $this->assertTrue(count($search1) > count($search2), 'Search 1: '.count($search1).' Search 2: '.count($search2));
    
});


it('should work with return limit', function () {
    $blues = Product::term('blue')->limit(5)->search();
    $this->assertTrue(count($blues) == 5);
    
});

it('sorted by boosted field', function () {
    $records = Product::term('silver', 3)->orTerm('blue')->field('color')->search();
    $currentColor = 'silver';
    foreach ($records as $record) {
        if ($record->color == 'blue' && $currentColor == 'silver') {
            $currentColor = 'blue';
        }
        $this->assertTrue($record->color == $currentColor, 'Record color: '.$record->color.' Current color: '.$currentColor);
    }
});


it('should work for fuzzy terms', function () {
    $silver = Product::term('silver')->search();
    $fuzzySilver = Product::fuzzyTerm('silvr')->search();
    $silverUsa = Product::term('silver')->orTerm('america')->andTerm('united')->search();
    $fuzzySilverUsa = Product::fuzzyTerm('silvr')->orFuzzyTerm('Amrica')->andFuzzyTerm('unitd')->search();
    
    $this->assertTrue(count($silver) == count($fuzzySilver), 'Silver: '.count($silver).' Fuzzy Silver: '.count($fuzzySilver));
    $this->assertTrue(count($silverUsa) <= count($fuzzySilverUsa), 'Silver USA: '.count($silverUsa).' Fuzzy Silver USA: '.count($fuzzySilverUsa));
});

it('should search with Geo filtering', function () {
    $allInArea = Product::filterGeoBox('manufacturer.location', [-20, 20], [20, -20])->get();
    $term = 'xxx';
    foreach ($allInArea as $a) {
        $term = $a->name;
    }
    $termWithGeo = Product::term($term)->filterGeoBox('manufacturer.location', [-20, 20], [20, -20])->search();
    
    $this->assertTrue(count($termWithGeo) <= count($allInArea), 'All in area: '.count($allInArea).' Term with geo: '.count($termWithGeo));
    
});

it('should highlight searches', function () {
    $silvers = Product::term('silver')->highlight()->search();
    $errorSearchHighlights = false;
    $errorSearchHighlightsAsArray = false;
    $errorWithHighlights = false;
    foreach ($silvers as $silver) {
        if (empty($silver->searchHighlights->color)) {
            $errorSearchHighlights = true;
        }
        if (empty($silver->searchHighlightsAsArray['color'])) {
            $errorSearchHighlightsAsArray = true;
        }
        if (!str_contains($silver->withHighlights->color, '<em>silver</em>')) {
            $errorWithHighlights = true;
        }
    }
    $this->assertFalse($errorSearchHighlights);
    $this->assertFalse($errorSearchHighlightsAsArray);
    $this->assertFalse($errorWithHighlights);
    
    
});

it('should remove the index', function () {
    Product::deleteIndexIfExists();
    $this->assertFalse(Schema::hasIndex('products'));
});