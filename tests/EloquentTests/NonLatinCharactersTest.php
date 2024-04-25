<?php

use PDPhilip\Elasticsearch\Schema\Schema;
use Tests\Factories\PostFactory;
use Tests\Models\Post;

$skip = false;


it('should remove the existing index if it exists', function () {
    Post::deleteIndexIfExists();
    $this->assertFalse(Schema::hasIndex('posts'));
});

it('should create posts', function () {
    $pf = new PostFactory();
    
    $i = 0;
    while ($i < 100) {
        $post = $pf->definition();
        if ($i % 10 == 1) {
            $post['title'] = $post['title'].' - президент';
        }
        if ($i % 10 == 2) {
            $post['title'] = $post['title'].' - gjøre';
        }
        
        if ($i % 10 == 3) {
            $post['title'] = $post['title'].' - '.'الحروف';
        }
        
        Post::createWithoutRefresh($post);
        $i++;
    }
    // Sleep to allow ES to catch up
    sleep(2);
    $find = Post::all();
    $this->assertTrue(count($find) === 100, 'Count is '.count($find));
});


it('should find posts with where() having cyrillics', function () {
    $posts = Post::where('title', 'президент')->get();
    $this->assertTrue(count($posts) === 10, 'Count is '.count($posts));
});

it('should find posts with  where() having nordic characters', function () {
    $posts = Post::where('title', 'gjøre')->get();
    $this->assertTrue(count($posts) === 10, 'Count is '.count($posts));
});

it('should find posts with  where() having arabic characters', function () {
    $posts = Post::where('title', 'الحروف')->get();
    $this->assertTrue(count($posts) === 10, 'Count is '.count($posts));
});

it('should find posts with term() having cyrillics', function () {
    $posts = Post::term('президент')->search();
    $this->assertTrue(count($posts) === 10, 'Count is '.count($posts));
});

it('should find posts with  term() having nordic characters', function () {
    $posts = Post::term('gjøre')->search();
    $this->assertTrue(count($posts) === 10, 'Count is '.count($posts));
});

it('should find posts with  term() having arabic characters', function () {
    $posts = Post::term('الحروف')->search();
    $this->assertTrue(count($posts) === 10, 'Count is '.count($posts));
});


it('should clean up', function () {
    Post::deleteIndexIfExists();
    $this->assertFalse(Schema::hasIndex('posts'));
});