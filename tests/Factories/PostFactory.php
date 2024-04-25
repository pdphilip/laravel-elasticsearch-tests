<?php

namespace tests\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use tests\Models\Post;

class PostFactory extends Factory
{
    protected $model = Post::class;
    
    public function definition(): array
    {
        return [
            'title'      => $this->faker->name(),
            'slug'       => $this->faker->slug(),
            'content'    => $this->faker->realTextBetween(100),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
