<?php

namespace Database\Factories;

use App\Models\Ad;
use App\Models\Address;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AdFactory extends Factory
{
    protected $model = Ad::class;

    public function definition(): array
    {
        return [
            'parent_id' => User::factory(),
            'title' => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'address_id' => Address::factory(),
            'date_start' => now()->addDay(),
            'date_end' => now()->addDay()->addHours(4),
            'hourly_rate' => $this->faker->randomFloat(2, 10, 30),
            'estimated_duration' => $this->faker->randomFloat(2, 2, 8),
            'estimated_total' => $this->faker->randomFloat(2, 50, 200),
            'children' => [
                [
                    'nom' => $this->faker->firstName(),
                    'age' => $this->faker->numberBetween(1, 12),
                    'unite' => 'ans'
                ]
            ],
            'status' => 'active',
            'is_guest' => false,
        ];
    }

    public function guest(): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => null,
            'is_guest' => true,
            'guest_email' => $this->faker->email(),
            'guest_firstname' => $this->faker->firstName(),
            'guest_token' => \Illuminate\Support\Str::random(32),
            'guest_expires_at' => now()->addDays(30),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'expired',
            'date_start' => now()->subDay(),
            'date_end' => now()->subDay()->addHours(4),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => 'other',
        ]);
    }
}