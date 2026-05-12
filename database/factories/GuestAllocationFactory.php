<?php

namespace Database\Factories;

use App\Models\GuestAllocation;
use App\Models\Session;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GuestAllocation>
 */
class GuestAllocationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'session_id' => Session::factory(),
            'guest_name' => fake()->name(),
            'pax' => fake()->numberBetween(1, 5),
            'source' => fake()->randomElement(['walk-in', 'phone', 'travel-agent']),
            'notes' => null,
            'status' => 'active',
            'allocated_by' => User::factory(),
        ];
    }

    /**
     * Indicate that the allocation has been cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => 'cancelled']);
    }
}
