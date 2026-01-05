<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class WorkoutSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = \App\Models\User::first();

        if (! $user) {
            $this->command->warn('No users found. Please create a user first.');

            return;
        }

        // Create some completed workouts in the past
        \App\Models\Workout::factory()
            ->for($user)
            ->count(5)
            ->create([
                'scheduled_at' => now()->subDays(rand(1, 14)),
                'completed_at' => now()->subDays(rand(1, 14)),
            ]);

        // Create workouts throughout the current month
        for ($day = 1; $day <= now()->daysInMonth; $day += 3) {
            $scheduledAt = now()->setDay($day)->setHour(rand(6, 20))->setMinute([0, 15, 30, 45][rand(0, 3)]);

            // Mix of completed and upcoming
            $completed = $scheduledAt->isPast() && rand(0, 1) === 1;

            \App\Models\Workout::factory()
                ->for($user)
                ->create([
                    'scheduled_at' => $scheduledAt,
                    'completed_at' => $completed ? $scheduledAt->copy()->addMinutes(rand(30, 90)) : null,
                ]);
        }

        // Create upcoming workouts in the next month
        \App\Models\Workout::factory()
            ->for($user)
            ->count(5)
            ->create([
                'scheduled_at' => now()->addDays(rand(1, 30)),
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
