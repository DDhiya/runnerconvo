<?php

namespace Database\Seeders;

use App\Models\Runner;
use Illuminate\Database\Seeder;

class RunnerSeeder extends Seeder
{
    /**
     * Seed the application's runners.
     *
     * Keyed by phone via updateOrCreate so re-running this seeder is safe and never
     * duplicates a runner. Note this DOES overwrite a name edited later in /admin —
     * that is the price of idempotency. Don't re-run this after launch expecting a
     * no-op if someone has since renamed a runner in the admin.
     */
    public function run(): void
    {
        $runners = [
            ['Hanizam', '60106554842'],
            ['Auni', '60133561949'],
            ['Dhiya', '60145332637'],
            ['Fazil', '60199215166'],
            ['Ezdie', '601121996364'],
            ['Farah', '60136969366'],
            ['Jue', '601165099515'],
            ['Azwarie', '60164475909'],
        ];

        foreach ($runners as $i => [$name, $phone]) {
            Runner::updateOrCreate(
                ['phone' => $phone],
                ['name' => $name, 'position' => $i + 1, 'is_active' => true],
            );
        }
    }
}
