<?php

namespace Database\Factories;

use App\Models\Attraction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attraction>
 */
class AttractionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'duration_minutes' => fake()->numberBetween(15, 180),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the attraction is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
