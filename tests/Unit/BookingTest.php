<?php

namespace Tests\Unit;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BookingOption;
use App\Models\Runner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\QueryException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('matrics')]
    public function test_normalise_matric(string $input, string $expected): void
    {
        $this->assertSame($expected, Booking::normaliseMatric($input));
    }

    /** @return array<string, array{string, string}> */
    public static function matrics(): array
    {
        return [
            'already clean' => ['CB22001', 'CB22001'],
            'lowercase' => ['cb22001', 'CB22001'],
            'spaces and hyphens' => [' cb 22-001 ', 'CB22001'],
        ];
    }

    public function test_reference_uses_the_unambiguous_alphabet(): void
    {
        $booking = Booking::factory()->create();

        $this->assertMatchesRegularExpression('/^JP-[A-HJKMNP-Z2-9]{6}$/', $booking->reference);
    }

    public function test_matric_rule_is_strict_for_diploma_and_bachelor_and_loose_otherwise(): void
    {
        $strict = fn (string $level, string $value) => preg_match(
            substr(Booking::matricRuleFor($level), strlen('regex:')),
            $value,
        ) === 1;

        foreach (['diploma', 'bachelor'] as $level) {
            $this->assertTrue($strict($level, 'CB22001'));
            $this->assertFalse($strict($level, 'CB22000'), 'running number starts at 001');
            $this->assertFalse($strict($level, 'CB2201'));
            $this->assertFalse($strict($level, 'CBA22001'));
        }

        $this->assertTrue($strict('phd', 'PHD21001'));
        $this->assertTrue($strict('master', 'MSC21001'));
    }

    public function test_move_to_stamps_each_stage_once(): void
    {
        $booking = Booking::factory()->create();

        $this->travelTo(now()->startOfSecond());
        $booking->moveTo(BookingStatus::Confirmed);
        $first = $booking->confirmed_at;

        $this->travel(5)->minutes();
        $booking->moveTo(BookingStatus::Submitted);
        $booking->moveTo(BookingStatus::Confirmed);

        $this->assertTrue($first->equalTo($booking->confirmed_at), 'first entry is kept when moved back and forth');
    }

    public function test_search_finds_a_phone_typed_with_a_leading_zero(): void
    {
        $target = Booking::factory()->create(['phone' => '60123456789']);
        Booking::factory()->create(['phone' => '60199999999']);

        $found = Booking::search('012-345')->pluck('id');

        $this->assertSame([$target->id], $found->all());
    }

    public function test_search_matches_reference_name_and_matric(): void
    {
        $booking = Booking::factory()->create(['full_name' => 'Aisyah Rahman', 'matric_no' => 'CB22123']);

        $this->assertCount(1, Booking::search($booking->reference)->get());
        $this->assertCount(1, Booking::search('aisyah')->get());
        $this->assertCount(1, Booking::search('cb 22-123')->get());
    }

    public function test_only_one_live_booking_per_matric_but_a_cancelled_one_frees_it(): void
    {
        Booking::factory()->cancelled()->create(['matric_no' => 'CB22001']);
        Booking::factory()->create(['matric_no' => 'CB22001']);

        $this->expectException(QueryException::class);
        Booking::factory()->create(['matric_no' => 'CB22001']);
    }

    public function test_an_option_in_use_reports_it_and_cannot_be_deleted_at_db_level(): void
    {
        $option = BookingOption::factory()->faculty()->create();
        $this->assertFalse($option->isInUse());

        Booking::factory()->create(['faculty_id' => $option->id]);

        $this->assertTrue($option->fresh()->isInUse());
        $this->expectException(QueryException::class);
        $option->delete();
    }

    public function test_deleting_a_runner_leaves_its_bookings_unassigned(): void
    {
        $runner = Runner::factory()->create();
        $booking = Booking::factory()->create(['runner_id' => $runner->id]);

        $runner->delete();

        $this->assertNull($booking->fresh()->runner_id);
    }
}
