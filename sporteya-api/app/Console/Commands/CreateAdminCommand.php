<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateAdminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sporteya:create-admin {email} {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new admin user with a secure generated password (Correction K2)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email');
        $name = $this->argument('name');

        if (User::where('email', $email)->exists()) {
            $this->error("User with email {$email} already exists.");
            return Command::FAILURE;
        }

        // Generate cryptographically secure random password
        $plainPassword = Str::random(16) . '!' . rand(10, 99);

        $admin = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($plainPassword),
            'role' => 'admin',
            'status' => 'active',
            'setup_complete' => true,
        ]);

        $this->info("Admin user created successfully.");
        $this->line("--------------------------------------------------");
        $this->line("ID:       {$admin->id}");
        $this->line("Name:     {$name}");
        $this->line("Email:    {$email}");
        $this->warn("Password: {$plainPassword}");
        $this->line("--------------------------------------------------");
        $this->warn("IMPORTANT: Copy this password now. It will NEVER be shown again.");

        return Command::SUCCESS;
    }
}
