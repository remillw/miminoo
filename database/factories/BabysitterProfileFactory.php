<?php

namespace Database\Factories;

use App\Models\BabysitterProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BabysitterProfileFactory extends Factory
{
    protected $model = BabysitterProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'description' => $this->faker->paragraph(),
            'hourly_rate' => $this->faker->randomFloat(2, 12, 25),
            'verification_status' => 'verified',
            'years_experience' => $this->faker->numberBetween(1, 10),
            'available_from' => '08:00',
            'available_to' => '20:00',
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'verification_status' => 'pending',
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'verification_status' => 'rejected',
        ]);
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'verification_status' => 'verified',
        ]);
    }
}