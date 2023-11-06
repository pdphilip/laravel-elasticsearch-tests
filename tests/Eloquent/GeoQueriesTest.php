<?php

use App\Models\Product;
use Database\Factories\ProductFactory;
use PDPhilip\Elasticsearch\Schema\IndexBlueprint;
use PDPhilip\Elasticsearch\Schema\Schema;
use PhpGeoMath\Model\Polar3dPoint;


$skip = false;

$fieldChecks = [
    'within1200'            => 0,
    'within1200WithStatus7' => 0,
    'geoBox20'              => 0,
    'geoBox20WithStatus7'   => 0,
];


function distanceCalculation($sourceLat, $sourceLon, $endLat, $endLon)
{
    $sourcePolar = new Polar3dPoint($sourceLat, $sourceLon, Polar3dPoint::EARTH_RADIUS_IN_METERS);
    $endPoint = new Polar3dPoint($endLat, $endLon, Polar3dPoint::EARTH_RADIUS_IN_METERS);
    
    return $endPoint->calcGeoDistanceToPoint($sourcePolar) / 1000;
}

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
    while ($i < 1000) {
        $product = $pf->definition();
        Product::createWithoutRefresh($product);
        
        //check if lat lon is within 1200km radius of 0,0
        $lat = $product['manufacturer']['location']['lat'];
        $lon = $product['manufacturer']['location']['lon'];
        if (-20 <= $lat && $lat <= 20) {
            if (-20 <= $lon && $lon <= 20) {
                $fieldChecks['geoBox20']++;
                if ($product['status'] == 7) {
                    $fieldChecks['geoBox20WithStatus7']++;
                }
            }
        }
        $distance = distanceCalculation(0, 0, $lat, $lon);
        
        if ($distance <= 1200) {
            $fieldChecks['within1200']++;
            if ($product['status'] == 7) {
                $fieldChecks['within1200WithStatus7']++;
            }
        }
        
        $i++;
    }
    // Sleep to allow ES to catch up
    sleep(2);
    $this->assertTrue(Schema::hasIndex('products'));
    $this->assertTrue(Product::count() === 1000, 'Count is '.Product::count());
})->skip($skip);


it('should filter geo point like a pro', function () use (&$fieldChecks) {
    $geo1 = Product::filterGeoPoint('manufacturer.location', '1200km', [0, 0])->get();
    $geo2 = Product::where('status', 7)->filterGeoPoint('manufacturer.location', '1200km', [0, 0])->get();
    
    $this->assertTrue(count($geo1) === $fieldChecks['within1200'], 'geo1 count is '.count($geo1).' and should be '.$fieldChecks['within1200']);
    $this->assertTrue(count($geo2) === $fieldChecks['within1200WithStatus7'], 'geo2 count is '.count($geo2).' and should be '.$fieldChecks['within1200WithStatus7']);
    Product::count();
    
})->skip($skip);

it('should filter geo box like a pro', function () use (&$fieldChecks) {
    $geo1 = Product::filterGeoBox('manufacturer.location', [-20, 20], [20, -20])->get();
    $geo2 = Product::where('status', 7)->filterGeoBox('manufacturer.location', [-20, 20], [20, -20])->get();
    
    $this->assertTrue(count($geo1) === $fieldChecks['geoBox20'], 'geo1 count is '.count($geo1).' and should be '.$fieldChecks['geoBox20']);
    $this->assertTrue(count($geo2) === $fieldChecks['geoBox20WithStatus7'], 'geo2 count is '.count($geo2).' and should be '.$fieldChecks['geoBox20WithStatus7']);
})->skip($skip);

it('should clean up', function () {
    Product::deleteIndexIfExists();
    $this->assertFalse(Schema::hasIndex('products'));
})->skip($skip);
