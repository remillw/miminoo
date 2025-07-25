<?php

namespace Database\Factories;

use App\Models\Address;
use Illuminate\Database\Eloquent\Factories\Factory;

class AddressFactory extends Factory
{
    protected $model = Address::class;

    public function definition(): array
    {
        return [
            'address' => $this->faker->streetAddress(),
            'postal_code' => $this->faker->postcode(),
            'country' => 'France',
            'latitude' => $this->faker->latitude(45, 50),
            'longitude' => $this->faker->longitude(0, 8),
        ];
    }

    public function paris(): static
    {
        return $this->state(fn (array $attributes) => [
            'address' => $this->faker->streetAddress() . ', Paris',
            'postal_code' => '750' . $this->faker->numberBetween(01, 20),
            'latitude' => $this->faker->latitude(48.815, 48.902),
            'longitude' => $this->faker->longitude(2.224, 2.469),
        ]);
    }
}