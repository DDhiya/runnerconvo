<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * No stock test user here on purpose — a stray `db:seed` in production must
     * never be able to create a login account with a known factory password.
     * The admin account is created separately via `php artisan jubahpanda:admin`.
     */
    public function run(): void
    {
        $this->call(RunnerSeeder::class);
    }
}
