<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WorkoutSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = \App\Models\User::first();

        if (!$user) {
            $this->command->warn('No users found. Please create a user first.');
            return;
        }

        // Create some completed workouts
        \App\Models\Workout::factory()
            ->for($user)
            ->count(5)
            ->create([
                'scheduled_at' => now()->subDays(rand(1, 14)),
                'completed_at' => now()->subDays(rand(1, 14)),
            ]);

        // Create some upcoming workouts
        \App\Models\Workout::factory()
            ->for($user)
            ->count(5)
            ->create([
                'scheduled_at' => now()->addDays(rand(1, 14)),
                'completed_at' => null,
            ]);

        // Create an overdue workout
        \App\Models\Workout::factory()
            ->for($user)
            ->create([
                'scheduled_at' => now()->subDays(2),
                'completed_at' => null,
            ]);

        $this->command->info('Workout seeder completed successfully.');
    }
}

