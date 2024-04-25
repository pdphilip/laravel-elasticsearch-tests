<?php

namespace Tests\Factories;

use Tests\Models\ClientProfile;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class ClientProfileFactory extends Factory
{
    protected $model = ClientProfile::class;
    
    public function definition(): array
    {
        return [
            'client_id'     => '',
            'contact_name'  => $this->faker->name(),
            'contact_email' => $this->faker->email(),
            'website'       => $this->faker->url(),
            'status'        => $this->faker->randomNumber(),
            'created_at'    => Carbon::now(),
            'updated_at'    => Carbon::now(),
        ];
    }
}
