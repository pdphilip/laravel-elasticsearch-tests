<?php

namespace Tests\Factories;

use Tests\Models\Person;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class PersonFactory extends Factory
{
    protected $model = Person::class;
    
    public function definition(): array
    {
        return [
            'name'       => $this->faker->name(),
            'jobs'       => $this->faker->words(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
