<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Development login only. Never seed known credentials outside local/testing.
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        // firstOrCreate keeps the seeder safe to run more than once.
        $user = User::firstOrCreate(
            ['email' => 'dev@faladev.test'],
            ['name' => 'Dev FalaDev', 'password' => 'password'],
        );

        // Fortify requires a verified e-mail to reach the dashboard.
        $user->forceFill(['email_verified_at' => $user->email_verified_at ?? now()])->save();
    }
}
