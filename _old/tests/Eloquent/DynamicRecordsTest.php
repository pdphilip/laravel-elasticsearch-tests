<?php

use App\Models\PageHit;
use PDPhilip\Elasticsearch\Schema\Schema;

$skip = false;

$dataCheck = [
    'dates'    => [],
    'pagesIds' => [],
];

it('will prepare the $dataCheck array', function () use (&$dataCheck) {
    $x = 1;
    while ($x < 10) {
        $dataCheck['pagesIds'][$x] = 0;
        $x++;
    }
    
    $j = 0;
    $date = '2021-01-01';
    while ($j < 10) {
        $k = 1;
        $dataCheck['dates'][$date] = [];
        while ($k < 10) {
            $dataCheck['dates'][$date][$k] = 0;
            $k++;
        }
        $date++;
        $j++;
    }
    $this->assertTrue(true);
});

it('should make dynamic indices from the same model', function () use (&$dataCheck) {
    Schema::deleteIfExists('page_hits_2021-01-01');
    Schema::deleteIfExists('page_hits_2021-01-02');
    Schema::deleteIfExists('page_hits_2021-01-03');
    Schema::deleteIfExists('page_hits_2021-01-04');
    Schema::deleteIfExists('page_hits_2021-01-05');
    Schema::deleteIfExists('page_hits_2021-01-06');
    Schema::deleteIfExists('page_hits_2021-01-07');
    Schema::deleteIfExists('page_hits_2021-01-08');
    Schema::deleteIfExists('page_hits_2021-01-09');
    Schema::deleteIfExists('page_hits_2021-01-10');
    $x = 1;
    $date = '2021-01-01';
    while ($x < 1000) {
        if ($x % 100 == 0) {
            $date++;
        }
        
        $pageId = (int)rand(1, 9);
        $dataCheck['pagesIds'][$pageId]++;
        $dataCheck['dates'][$date][$pageId]++;
        $pageHit = new PageHit;
        $pageHit->ip = '192.168.0.1';
        $pageHit->page_id = $pageId;
        $pageHit->date = $date;
        $pageHit->setIndex('page_hits_'.$date);
        $pageHit->saveWithoutRefresh();
        $x++;
    }
    
    //sleep to allow ES to catch up
    sleep(1);
    
    $this->assertTrue(PageHit::count() === 999);
    
})->skip($skip);

it('should be able to search across all indices', function () use (&$dataCheck) {
    $pageHits = PageHit::where('page_id', 9)->get();
    
    $this->assertTrue(count($pageHits) === $dataCheck['pagesIds'][9]);
})->skip($skip);

it('should be able to update across all indices', function () use (&$dataCheck) {
    $pageHits = PageHit::where('page_id', 9)->get();
    if ($pageHits) {
        foreach ($pageHits as $pageHit) {
            $pageHit->page_id = (int)99;
            $pageHit->saveWithoutRefresh();
        }
    }
    // Sleep to allow ES to catch up
    sleep(1);
    
    $pageHits9 = PageHit::where('page_id', 9)->get();
    $pageHits99 = PageHit::where('page_id', 99)->get();
    $this->assertTrue(count($pageHits9) === 0);
    $this->assertTrue(count($pageHits99) === $dataCheck['pagesIds'][9]);
    
})->skip($skip);


it('should be able to search within a specific index', function () use (&$dataCheck) {
    $pageHits = new PageHit();
    $pageHits->setIndex('page_hits_2021-01-01');
    $found = $pageHits->where('page_id', 3)->get();
    
    $this->assertTrue(count($found) === $dataCheck['dates']['2021-01-01'][3]);
    
})->skip($skip);

it('should be able to delete all dynamic indices', function () {
    $pageHits = PageHit::all();
    $pageHits->each->delete();
    // Sleep to allow ES to catch up
    sleep(1);
    $this->assertTrue(PageHit::count() === 0);
})->skip($skip);

it('should remove all the indices', function () {
    Schema::deleteIfExists('page_hits_2021-01-01');
    Schema::deleteIfExists('page_hits_2021-01-02');
    Schema::deleteIfExists('page_hits_2021-01-03');
    Schema::deleteIfExists('page_hits_2021-01-04');
    Schema::deleteIfExists('page_hits_2021-01-05');
    Schema::deleteIfExists('page_hits_2021-01-06');
    Schema::deleteIfExists('page_hits_2021-01-07');
    Schema::deleteIfExists('page_hits_2021-01-08');
    Schema::deleteIfExists('page_hits_2021-01-09');
    Schema::deleteIfExists('page_hits_2021-01-10');
    $this->assertFalse(Schema::hasIndex('page_hits_2021-01-01'));
    $this->assertFalse(Schema::hasIndex('page_hits_2021-01-02'));
    $this->assertFalse(Schema::hasIndex('page_hits_2021-01-03'));
    $this->assertFalse(Schema::hasIndex('page_hits_2021-01-04'));
    $this->assertFalse(Schema::hasIndex('page_hits_2021-01-05'));
    $this->assertFalse(Schema::hasIndex('page_hits_2021-01-06'));
    $this->assertFalse(Schema::hasIndex('page_hits_2021-01-07'));
    $this->assertFalse(Schema::hasIndex('page_hits_2021-01-08'));
    $this->assertFalse(Schema::hasIndex('page_hits_2021-01-09'));
    $this->assertFalse(Schema::hasIndex('page_hits_2021-01-10'));
    
})->skip($skip);