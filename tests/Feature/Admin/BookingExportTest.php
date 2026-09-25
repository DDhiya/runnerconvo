<?php

namespace Tests\Feature\Admin;

use App\Models\Booking;
use App\Models\Runner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class BookingExportTest extends TestCase
{
    use RefreshDatabase;

    private function csv(array $query = []): string
    {
        $response = $this->actingAs(User::factory()->create())->get(route('admin.bookings.export', $query));
        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));

        return $response->streamedContent();
    }

    public function test_the_export_has_a_bom_a_header_row_and_one_line_per_booking(): void
    {
        $runner = Runner::factory()->create(['name' => 'Hanizam']);
        Booking::factory()->create(['full_name' => 'Aisyah Rahman', 'runner_id' => $runner->id, 'email' => 'aisyah@example.com']);

        $csv = $this->csv();

        $this->assertStringContainsString('Phone,Email,', $csv);
        $this->assertStringContainsString('aisyah@example.com', $csv);

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString('Reference,Created,Status,"Full name"', $csv);
        $this->assertStringContainsString('"Aisyah Rahman"', $csv);
        $this->assertStringContainsString('Hanizam', $csv);
        $this->assertStringContainsString('45.00', $csv);
    }

    public function test_the_export_respects_the_same_filters_as_the_list(): void
    {
        Booking::factory()->create(['full_name' => 'Keep Me']);
        Booking::factory()->cancelled()->create(['full_name' => 'Drop Me']);

        $csv = $this->csv(['status' => 'cancelled']);

        $this->assertStringContainsString('Drop Me', $csv);
        $this->assertStringNotContainsString('Keep Me', $csv);
    }

    public function test_cells_that_could_run_as_spreadsheet_formulas_are_neutralised(): void
    {
        Booking::factory()->create(['full_name' => '=HYPERLINK("http://evil.example","click")', 'notes' => '@SUM(1+1)']);

        $csv = $this->csv();

        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringContainsString("'@SUM", $csv);
        $this->assertStringNotContainsString(',=HYPERLINK', $csv);
    }

    public function test_every_export_is_logged_with_who_and_which_filters_but_no_personal_data(): void
    {
        Booking::factory()->create(['full_name' => 'Private Person']);
        Log::spy();

        $this->csv(['paid' => 'no']);

        Log::shouldHaveReceived('notice')->withArgs(function (string $message, array $context) {
            return $message === 'bookings.exported'
                && $context['filters'] === ['paid' => 'no']
                && $context['rows'] === 1
                && ! str_contains(json_encode($context), 'Private Person');
        })->once();
    }
}
