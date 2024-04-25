<?php

namespace Tests\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Tests\Models\UserProfile;

class UserProfileFactory extends Factory
{
    protected $model = UserProfile::class;
    
    public function definition(): array
    {
        return [
            'twitter'    => $this->faker->word(),
            'facebook'   => $this->faker->word(),
            'address'    => $this->faker->address(),
            'timezone'   => $this->faker->timezone(),
            'status'     => $this->faker->randomNumber(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
