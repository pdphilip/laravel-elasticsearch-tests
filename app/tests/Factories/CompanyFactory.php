<?php

namespace Tests\Factories;

use Tests\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class CompanyFactory extends Factory
{
    protected $model = Company::class;
    
    public function definition(): array
    {
        return [
            'name'       => $this->faker->company(),
            'status'     => $this->faker->randomNumber(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
    
}
