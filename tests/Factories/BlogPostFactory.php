<?php

namespace tests\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use tests\Models\BlogPost;

class BlogPostFactory extends Factory
{
    protected $model = BlogPost::class;
    
    public function generateRandomCountry()
    {
        $countries = [
            'USA',
            'UK',
            'Canada',
            'Australia',
            'Germany',
            'France',
            'Netherlands',
            'Austria',
            'Switzerland',
            'Sweden',
            'Norway',
            'Denmark',
            'Finland',
            'Belgium',
            'Italy',
            'Spain',
            'Portugal',
            'Greece',
            'Ireland',
            'Poland',
            'Peru',
        ];
        
        return $countries[rand(0, count($countries) - 1)];
    }
    
    
    public function generateComments($count)
    {
        $comments = [];
        for ($i = 0; $i < $count; $i++) {
            $comment = [
                'name'    => $this->faker->name(),
                'comment' => $this->faker->text(),
                'country' => $this->generateRandomCountry(),
                'likes'   => rand(0, 10),
            
            ];
            $comments[] = $comment;
        }
        
        return $comments;
    }
    
    public function definition(): array
    {
        
        return [
            'title'      => $this->faker->word(),
            'content'    => $this->faker->word(),
            'comments'   => $this->generateComments(rand(5, 20)),
            'status'     => rand(1, 5),
            'active'     => $this->faker->boolean(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
