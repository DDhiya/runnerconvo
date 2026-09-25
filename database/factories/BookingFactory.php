<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BookingOption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'full_name' => fake()->name(),
            'matric_no' => 'CB22'.str_pad((string) fake()->unique()->numberBetween(1, 999), 3, '0', STR_PAD_LEFT),
            'phone' => '601'.fake()->unique()->numerify('########'),
            'faculty_id' => BookingOption::factory()->faculty(),
            'robe_size_id' => BookingOption::factory()->robeSize(),
            'convocation_session_id' => BookingOption::factory()->session(),
            'programme_level' => 'bachelor',
            'delivery_method' => 'pickup',
            'delivery_address' => null,
            'notes' => null,
            'locale' => 'en',
            'status' => BookingStatus::Submitted,
            'amount_sen' => 4500,
            'consented_at' => now(),
            'privacy_version' => '2026-09-25',
        ];
    }

    public function cod(): static
    {
        return $this->state([
            'delivery_method' => 'cod',
            'delivery_address' => 'No. 1, Jalan Test, 25200 Kuantan, Pahang',
        ]);
    }

    public function paid(): static
    {
        return $this->state([
            'paid_at' => now(),
            'payment_method' => 'transfer',
        ]);
    }

    public function cancelled(): static
    {
        return $this->status(BookingStatus::Cancelled);
    }

    public function status(BookingStatus $status): static
    {
        $column = $status->timestampColumn();

        return $this->state(array_filter([
            'status' => $status,
            $column => $column ? now() : null,
        ], fn ($value) => $value !== null));
    }
}
