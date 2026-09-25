<?php

namespace Database\Factories;

use App\Enums\BookingOptionType;
use App\Models\BookingOption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookingOption>
 */
class BookingOptionFactory extends Factory
{
    public function definition(): array
    {
        $label = fake()->unique()->words(3, true);

        return [
            'type' => BookingOptionType::Faculty,
            'label_en' => ucfirst($label),
            'label_ms' => ucfirst($label).' (BM)',
            'position' => fake()->unique()->numberBetween(1, 9999),
            'is_active' => true,
        ];
    }

    public function faculty(): static
    {
        return $this->state(['type' => BookingOptionType::Faculty]);
    }

    public function robeSize(): static
    {
        return $this->state(['type' => BookingOptionType::RobeSize]);
    }

    public function session(): static
    {
        return $this->state(['type' => BookingOptionType::ConvocationSession]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
