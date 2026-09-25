<?php

namespace Tests\Feature;

use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurgeBookingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_bookings_past_retention_are_deleted_and_recent_ones_kept(): void
    {
        $old = Booking::factory()->create(['created_at' => now()->subDays(366)]);
        $oldCancelled = Booking::factory()->cancelled()->create(['created_at' => now()->subDays(400)]);
        $recent = Booking::factory()->create(['created_at' => now()->subDays(364)]);

        $this->artisan('jubahpanda:purge-bookings')->assertSuccessful();

        $this->assertDatabaseMissing('bookings', ['id' => $old->id]);
        $this->assertDatabaseMissing('bookings', ['id' => $oldCancelled->id]);
        $this->assertDatabaseHas('bookings', ['id' => $recent->id]);
    }

    public function test_dry_run_deletes_nothing(): void
    {
        Booking::factory()->create(['created_at' => now()->subDays(500)]);

        $this->artisan('jubahpanda:purge-bookings', ['--dry-run' => true])
            ->expectsOutputToContain('Would delete 1 booking(s)')
            ->assertSuccessful();

        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_production_refuses_without_force(): void
    {
        Booking::factory()->create(['created_at' => now()->subDays(500)]);
        $this->app['env'] = 'production';

        $this->artisan('jubahpanda:purge-bookings')->expectsConfirmation('Are you sure you want to run this command?', 'no')->assertFailed();

        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_production_runs_with_force_as_cron_does(): void
    {
        Booking::factory()->create(['created_at' => now()->subDays(500)]);
        $this->app['env'] = 'production';

        $this->artisan('jubahpanda:purge-bookings', ['--force' => true])->assertSuccessful();

        $this->assertDatabaseCount('bookings', 0);
    }
}
