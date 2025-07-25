<?php

namespace Database\Factories;

use App\Models\AdApplication;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReservationFactory extends Factory
{
    protected $model = Reservation::class;

    public function definition(): array
    {
        $hourlyRate = $this->faker->randomFloat(2, 12, 25);
        $depositAmount = $hourlyRate * 3; // 3 hours deposit
        $serviceFee = $depositAmount * 0.1; // 10% service fee
        $totalDeposit = $depositAmount + $serviceFee;

        return [
            'parent_id' => User::factory(),
            'babysitter_id' => User::factory(),
            'application_id' => AdApplication::factory(),
            'status' => 'pending_payment',
            'hourly_rate' => $hourlyRate,
            'deposit_amount' => $depositAmount,
            'service_fee' => $serviceFee,
            'total_deposit' => $totalDeposit,
            'babysitter_amount' => $depositAmount,
            'reserved_at' => now(),
            'payment_due_at' => now()->addHours(2),
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paid',
            'paid_at' => now(),
            'stripe_payment_intent_id' => 'pi_test_' . $this->faker->uuid(),
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'paid_at' => now()->subHour(),
            'service_start_at' => now(),
            'stripe_payment_intent_id' => 'pi_test_' . $this->faker->uuid(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'service_completed',
            'paid_at' => now()->subHours(5),
            'service_start_at' => now()->subHours(4),
            'service_end_at' => now(),
            'funds_released_at' => now()->addDay(),
            'stripe_payment_intent_id' => 'pi_test_' . $this->faker->uuid(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled_by_parent',
            'cancelled_at' => now(),
            'cancellation_reason' => 'parent_unavailable',
        ]);
    }
}