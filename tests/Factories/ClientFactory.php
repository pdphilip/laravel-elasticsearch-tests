<?php

namespace tests\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use tests\Models\Client;

class ClientFactory extends Factory
{
    protected $model = Client::class;
    
    public function definition(): array
    {
        return [
            'company_id' => '',
            'name'       => $this->faker->name(),
            'status'     => $this->faker->randomNumber(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
