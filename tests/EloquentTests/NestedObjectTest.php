<?php

use PDPhilip\Elasticsearch\Schema\IndexBlueprint;
use PDPhilip\Elasticsearch\Schema\Schema;
use Tests\Factories\BlogPostFactory;
use Tests\Models\BlogPost;


$fieldChecks = [
    'hasCommentFromPeru'               => 0,
    'hasCommentFromPeruWith5Likes'     => 0,
    'hasCommentFromPeruWith1or10Likes' => 0,
    'hasCommentFromPeruOr5Likes'       => 0,
    'hasCommentFromPeruAndStatus1'     => 0,
    'totalCommentsFromPeruWithStatus1' => 0,
    'idsByTotalLikes'                  => [],
];
function createPosts()
{
    $fieldChecks = [
        'hasCommentFromPeru'               => 0,
        'hasCommentFromPeruWith5Likes'     => 0,
        'hasCommentFromPeruWith1or10Likes' => 0,
        'hasCommentFromPeruOr5Likes'       => 0,
        'hasCommentFromPeruAndStatus1'     => 0,
        'totalCommentsFromPeruWithStatus1' => 0,
        'idsByTotalLikes'                  => [],
    ];
    $postFactory = new BlogPostFactory();
    
    $i = 0;
    while ($i < 100) {
        $post = $postFactory->definition();
        $triggeredHasCommentFromPeru = false;
        $triggeredHasCommentFromPeruWith5Likes = false;
        $triggeredHasCommentFromPeruWith1or10Likes = false;
        $triggeredHasCommentFromPeruAndStatus1 = false;
        $triggered5Likes = false;
        $totalLikes = 0;
        foreach ($post['comments'] as $comment) {
            $totalLikes += $comment['likes'];
            if ($comment['country'] === 'Peru') {
                $triggeredHasCommentFromPeru = true;
                if ($comment['likes'] === 5) {
                    $triggeredHasCommentFromPeruWith5Likes = true;
                }
                if ($comment['likes'] === 1 || $comment['likes'] === 10) {
                    $triggeredHasCommentFromPeruWith1or10Likes = true;
                }
                if ($post['status'] === 1) {
                    $fieldChecks['totalCommentsFromPeruWithStatus1']++;
                    $triggeredHasCommentFromPeruAndStatus1 = true;
                }
            } else {
                if ($comment['likes'] === 5) {
                    $triggered5Likes = true;
                }
            }
        }
        if ($triggeredHasCommentFromPeru) {
            $fieldChecks['hasCommentFromPeru']++;
        }
        if ($triggeredHasCommentFromPeruWith5Likes) {
            $fieldChecks['hasCommentFromPeruWith5Likes']++;
        }
        if ($triggeredHasCommentFromPeruAndStatus1) {
            $fieldChecks['hasCommentFromPeruAndStatus1']++;
        }
        if ($triggeredHasCommentFromPeruWith1or10Likes) {
            $fieldChecks['hasCommentFromPeruWith1or10Likes']++;
        }
        
        if ($triggered5Likes || $triggeredHasCommentFromPeru) {
            $fieldChecks['hasCommentFromPeruOr5Likes']++;
        }
        
        $blogPost = new BlogPost;
        foreach ($post as $key => $value) {
            $blogPost->$key = $value;
        }
        $blogPost->saveWithoutRefresh();
        $fieldChecks['idsByTotalLikes'][$blogPost->_id] = $totalLikes;
        $i++;
    }
    if (!$fieldChecks['hasCommentFromPeru']
        || !$fieldChecks['hasCommentFromPeruWith5Likes']
        || !$fieldChecks['hasCommentFromPeruWith1or10Likes']
        || !$fieldChecks['hasCommentFromPeruOr5Likes']
        || !$fieldChecks['totalCommentsFromPeruWithStatus1']
        || !$fieldChecks['hasCommentFromPeruAndStatus1']) {
        BlogPost::deleteIndexIfExists();
        Schema::create('blog_posts', function (IndexBlueprint $index) {
            $index->nested('comments');
        });
        createPosts();
    }
    
    return $fieldChecks;
}

it('should remove the existing index if it exists', function () {
    BlogPost::deleteIndexIfExists();
    $this->assertFalse(Schema::hasIndex('blog_posts'));
});

it('should create a nested index', function () {
    Schema::create('blog_posts', function (IndexBlueprint $index) {
        $index->nested('comments');
    });
    $this->assertTrue(Schema::hasIndex('blog_posts'));
});

it('should create posts', function () use (&$fieldChecks) {
    $checks = createPosts();
    $fieldChecks = $checks;
    arsort($fieldChecks['idsByTotalLikes']);
    // Sleep to allow ES to catch up
    sleep(2);
    $this->assertTrue(Schema::hasIndex('blog_posts'));
    $this->assertTrue(BlogPost::count() === 100, 'Should be 100, but is '.BlogPost::count());
});

it('should find posts with comments from Peru', function () use (&$fieldChecks) {
    $posts = BlogPost::whereNestedObject('comments', function ($query) {
        $query->where('comments.country', 'Peru');
    })->get();
    $this->assertTrue(count($posts) === $fieldChecks['hasCommentFromPeru'], 'Should be '.$fieldChecks['hasCommentFromPeru'].', but is '.count($posts));
});

it('should find posts without comments from Peru', function () use (&$fieldChecks) {
    $posts = BlogPost::whereNotNestedObject('comments', function ($query) {
        $query->where('comments.country', 'Peru');
    })->get();
    $check = 100 - $fieldChecks['hasCommentFromPeru'];
    $this->assertTrue(count($posts) === $check, 'Should be '.$check.', but is '.count($posts));
});

it('should find posts with comments from Peru and 5 likes', function () use (&$fieldChecks) {
    $posts = BlogPost::whereNestedObject('comments', function ($query) {
        $query->where('comments.country', 'Peru')->where('comments.likes', 5);
    })->get();
    $this->assertTrue(count($posts) === $fieldChecks['hasCommentFromPeruWith5Likes']);
});

it('should find posts with comments from Peru and 1 or 10 likes', function () use (&$fieldChecks) {
    $posts = BlogPost::whereNestedObject('comments', function ($query) {
        $query->where('comments.country', 'Peru')->whereIn('comments.likes', [1, 10]);
    })->get();
    $this->assertTrue(count($posts) === $fieldChecks['hasCommentFromPeruWith1or10Likes']);
});

it('should find posts with comments from Peru or 5 likes', function () use (&$fieldChecks) {
    $posts = BlogPost::whereNestedObject('comments', function ($query) {
        $query->where('comments.country', 'Peru')->orWhere('comments.likes', 5);
    })->get();
    $this->assertTrue(count($posts) === $fieldChecks['hasCommentFromPeruOr5Likes']);
});

it('should find posts with comments from Peru and status 1', function () use (&$fieldChecks) {
    $posts = BlogPost::where('status', 1)->whereNestedObject('comments', function ($query) {
        $query->where('comments.country', 'Peru');
    })->get();
    $this->assertTrue(count($posts) === $fieldChecks['hasCommentFromPeruAndStatus1']);
});


it('should find posts with comments from Peru (via direct obj field)', function () use (&$fieldChecks) {
    $posts = BlogPost::whereNestedObject('comments', function ($query) {
        $query->where('country', 'Peru');
    })->get();
    $this->assertTrue(count($posts) === $fieldChecks['hasCommentFromPeru'], 'Should be '.$fieldChecks['hasCommentFromPeru'].', but is '.count($posts));
});

it('should find posts with comments from Peru and 5 likes (via direct obj field)', function () use (&$fieldChecks) {
    $posts = BlogPost::whereNestedObject('comments', function ($query) {
        $query->where('country', 'Peru')->where('likes', 5);
    })->get();
    $this->assertTrue(count($posts) === $fieldChecks['hasCommentFromPeruWith5Likes']);
});

it('should find posts with comments from Peru and 1 or 10 likes (via direct obj field)', function () use (&$fieldChecks) {
    $posts = BlogPost::whereNestedObject('comments', function ($query) {
        $query->where('country', 'Peru')->whereIn('likes', [1, 10]);
    })->get();
    $this->assertTrue(count($posts) === $fieldChecks['hasCommentFromPeruWith1or10Likes']);
});

it('should find posts with comments from Peru or 5 likes (via direct obj field)', function () use (&$fieldChecks) {
    $posts = BlogPost::whereNestedObject('comments', function ($query) {
        $query->where('country', 'Peru')->orWhere('likes', 5);
    })->get();
    $this->assertTrue(count($posts) === $fieldChecks['hasCommentFromPeruOr5Likes']);
});

it('should find posts with comments from Peru and status 1 (via direct obj field)', function () use (&$fieldChecks) {
    $posts = BlogPost::where('status', 1)->whereNestedObject('comments', function ($query) {
        $query->where('country', 'Peru');
    })->get();
    $this->assertTrue(count($posts) === $fieldChecks['hasCommentFromPeruAndStatus1']);
});

it('should order by nested field', function () use (&$fieldChecks) {
    $posts = BlogPost::orderByNested('comments.likes', 'desc', 'sum')->limit(10)->get();
    $top10 = array_slice($fieldChecks['idsByTotalLikes'], 0, 10);
    $failedToFind = false;
    foreach ($posts as $post) {
        if (empty($top10[$post->_id])) {
            $failedToFind = true;
        }
    }
    $this->assertFalse($failedToFind);
});

it('should filter nested values', function () use (&$fieldChecks) {
    $posts = BlogPost::where('status', 1)->queryNested('comments', function ($query) {
        $query->where('country', 'Peru');
    })->get();
    $foundWrongCountry = false;
    $totalFound = 0;
    foreach ($posts as $post) {
        if (!empty($post->comments)) {
            foreach ($post->comments as $comment) {
                if ($comment['country'] !== 'Peru') {
                    $foundWrongCountry = $comment['country'];
                } else {
                    $totalFound++;
                }
                
            }
        }
        
    }
    $this->assertTrue($totalFound === $fieldChecks['totalCommentsFromPeruWithStatus1']);
    $this->assertFalse($foundWrongCountry, 'Found wrong country: '.$foundWrongCountry);
});


it('should clean up', function () {
    BlogPost::deleteIndexIfExists();
    $this->assertFalse(Schema::hasIndex('soft_products'));
});