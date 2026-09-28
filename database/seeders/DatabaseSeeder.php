<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // A test@example.com / "password" (UserFactory's default) account has no
        // business existing outside local dev — guarded rather than deleted, since
        // this seeder still runs to bootstrap fixtures on a real local install.
        if (! app()->environment('local')) {
            return;
        }

        User::firstOrCreate(
            ['email' => 'test@example.com'],
            ['name' => 'Test User'],
        );
    }
}
