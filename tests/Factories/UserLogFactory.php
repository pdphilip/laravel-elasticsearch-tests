<?php

namespace Tests\Factories;

use Tests\Models\UserLog;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class UserLogFactory extends Factory
{
    protected $model = UserLog::class;
    
    public function definition(): array
    {
        $devices = ['desktop', 'mobile', 'tablet'];
        shuffle($devices);
        
        return [
            'title'      => $this->faker->word(),
            'score'      => $this->faker->word(),
            'secret'     => $this->faker->word(),
            'code'       => rand(1, 5),
            'meta'       => [],
            'agent'      => [
                'ip'          => $this->faker->ipv4(),
                'source'      => $this->faker->url(),
                'method'      => 'GET',
                'browser'     => 'Chrome',
                'device'      => 'Chrome',
                'deviceType'  => $devices[0],
                'geo'         => [
                    'lat' => $this->faker->latitude(),
                    'lon' => $this->faker->longitude(),
                ],
                'countryCode' => $this->faker->countryCode(),
                'city'        => $this->faker->city(),
            ],
            'status'     => rand(1, 9),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}

