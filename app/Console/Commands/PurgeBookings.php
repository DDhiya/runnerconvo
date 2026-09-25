<?php

namespace App\Console\Commands;

use App\Models\Booking;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;

class PurgeBookings extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jubahpanda:purge-bookings
                            {--dry-run : Show what would be deleted without deleting it}
                            {--force : Run in production without the confirmation prompt (cron needs this)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete bookings older than the retention period (PDPA: one year)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! $this->option('dry-run') && ! $this->confirmToProceed()) {
            return self::FAILURE;
        }

        $days = (int) config('jubahrunner.retention_days');
        $cutoff = now()->subDays($days);

        // Whatever the status: a cancelled or finished booking is personal data too. The
        // clock runs from registration, which is the moment the graduate consented.
        $query = Booking::query()->where('created_at', '<', $cutoff);
        $count = $query->count();

        if ($this->option('dry-run')) {
            $this->info("Would delete {$count} booking(s) registered before {$cutoff->toDateString()}.");

            return self::SUCCESS;
        }

        $query->delete();

        // No personal data in the log line.
        $this->info("Deleted {$count} booking(s) registered before {$cutoff->toDateString()}.");

        return self::SUCCESS;
    }
}
