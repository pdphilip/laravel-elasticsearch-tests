<?php

namespace Tests\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Tests\Models\Person;

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
