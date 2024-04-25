<?php

namespace Tests\Factories;

use Tests\Models\CompanyProfile;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class CompanyProfileFactory extends Factory
{
    protected $model = CompanyProfile::class;
    
    public function definition(): array
    {
        return [
            'address'    => $this->faker->address(),
            'website'    => $this->faker->url(),
            'status'     => $this->faker->randomNumber(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
