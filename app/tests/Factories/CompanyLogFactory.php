<?php

namespace Tests\Factories;

use Tests\Models\CompanyLog;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class CompanyLogFactory extends Factory
{
    protected $model = CompanyLog::class;
    
    public function definition(): array
    {
        return [
            'company_id' => '',
            'title'      => $this->faker->word(),
            'desc'       => $this->faker->sentence(),
            'status'     => $this->faker->randomNumber(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
