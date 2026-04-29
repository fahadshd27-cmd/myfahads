<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Keep baseline users for local testing.
        User::query()->updateOrCreate([
            'email' => 'test@example.com',
        ], [
            'name' => 'Test User',
            'display_name' => 'Test User',
            'password' => Hash::make('password'),
            'status' => 'active',
            'is_admin' => false,
            'email_verified_at' => now(),
            'country' => 'US',
            'timezone' => 'America/New_York',
        ]);

        $this->call(SpinnerDemoSeeder::class);
    }
}
