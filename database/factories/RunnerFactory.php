<?php

namespace Database\Factories;

use App\Models\Runner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Runner>
 */
class RunnerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->firstName(),
            'phone' => '601'.fake()->unique()->numerify('########'),
            'position' => fake()->unique()->numberBetween(1, 999),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the runner is deactivated and should be hidden from the public list.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
