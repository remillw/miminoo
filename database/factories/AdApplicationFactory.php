<?php

namespace Database\Factories;

use App\Models\Ad;
use App\Models\AdApplication;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AdApplicationFactory extends Factory
{
    protected $model = AdApplication::class;

    public function definition(): array
    {
        return [
            'ad_id' => Ad::factory(),
            'babysitter_id' => User::factory(),
            'status' => 'pending',
            'motivation_note' => $this->faker->paragraph(),
            'proposed_rate' => $this->faker->randomFloat(2, 12, 25),
        ];
    }

    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'accepted',
        ]);
    }

    public function declined(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'declined',
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
    }

    public function withCounterOffer(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'counter_offered',
            'counter_rate' => $this->faker->randomFloat(2, 15, 30),
        ]);
    }
}