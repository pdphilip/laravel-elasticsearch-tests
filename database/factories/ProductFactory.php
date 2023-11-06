<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'         => $this->faker->name(),
            'product_id'   => $this->faker->uuid(),
            'in_stock'     => rand(0, 100),
            'status'       => rand(1, 9),
            'color'        => $this->faker->safeColorName(),
            'is_active'    => $this->faker->boolean(),
            'price'        => $this->faker->randomFloat(2, 0, 2000),
            'orders'       => rand(0, 250),
            'manufacturer' => [
                'location' => [
                    'lat' => $this->faker->latitude(),
                    'lon' => $this->faker->longitude(),
                ],
                'name'     => $this->faker->company(),
                'country'  => $this->faker->country(),
                'owned_by' => [
                    'name'    => $this->faker->name(),
                    'country' => $this->faker->country(),
                ],
            ],
            'created_at'   => Carbon::now(),
            'updated_at'   => Carbon::now(),
        ];
    }
    
}
