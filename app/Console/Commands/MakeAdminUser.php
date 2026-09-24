<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jubahpanda:admin
                            {email : The admin login email}
                            {--name=JubahPanda Admin : The admin display name}
                            {--password= : Skip the prompt (avoid — lands in shell history)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create or reset the admin login used at /admin';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $password = $this->option('password') ?: $this->secret('Password');

        if (blank($password) || mb_strlen($password) < 12) {
            $this->error('Password must be at least 12 characters.');

            return self::FAILURE;
        }

        // `password` is cast `hashed` on User, and the cast guards with
        // Hash::isHashed(), so passing the plaintext here hashes exactly once.
        // updateOrCreate means this command also doubles as the password-reset path.
        $user = User::updateOrCreate(
            ['email' => $this->argument('email')],
            [
                'name' => $this->option('name'),
                'password' => $password,
                'email_verified_at' => now(),
            ],
        );

        $this->info("Admin ready: {$user->email}");

        return self::SUCCESS;
    }
}
