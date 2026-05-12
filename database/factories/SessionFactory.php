<?php

namespace Database\Factories;

use App\Enums\SessionStatus;
use App\Models\Attraction;
use App\Models\Session;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Session>
 */
class SessionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('now', '+30 days');

        return [
            'attraction_id' => Attraction::factory(),
            'start_time' => $start,
            'end_time' => (clone $start)->modify('+'.fake()->numberBetween(30, 120).' minutes'),
            'max_capacity' => fake()->numberBetween(5, 100),
            'current_pax' => 0,
            'status' => SessionStatus::Active,
        ];
    }

    /**
     * Indicate that the session is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn () => ['status' => SessionStatus::Inactive]);
    }

    /**
     * Set the current occupancy of the session.
     */
    public function withOccupancy(int $pax): static
    {
        return $this->state(fn () => ['current_pax' => $pax]);
    }
}
