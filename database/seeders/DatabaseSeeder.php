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
        // User::factory(10)->create();

        User::factory()->withoutTwoFactor()->create([
            'name' => 'Tijmen Wierenga',
            'email' => 't.wierenga@live.nl',
            'password' => '12345678',
        ]);
    }
}
