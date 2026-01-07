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

        // Create a sample workout with steps for demonstration
        $sampleWorkout = \App\Models\Workout::factory()
            ->for($user)
            ->create([
                'name' => 'Sample 5K Interval Training',
                'sport' => 'running',
                'scheduled_at' => now()->addDays(1)->setHour(8)->setMinute(0),
                'completed_at' => null,
            ]);

        // Add warmup
        $sampleWorkout->allSteps()->create([
            'sort_order' => 0,
            'step_kind' => 'warmup',
            'intensity' => 'warmup',
            'duration_type' => 'time',
            'duration_value' => 600, // 10 minutes
            'target_type' => 'none',
        ]);

        // Add repeat block with intervals
        $repeat = $sampleWorkout->allSteps()->create([
            'sort_order' => 1,
            'step_kind' => 'repeat',
            'intensity' => 'active',
            'repeat_count' => 5,
            'skip_last_recovery' => false,
        ]);

        // Run interval
        $sampleWorkout->allSteps()->create([
            'parent_step_id' => $repeat->id,
            'sort_order' => 0,
            'step_kind' => 'run',
            'intensity' => 'active',
            'duration_type' => 'distance',
            'duration_value' => 1000, // 1 km
            'target_type' => 'pace',
            'target_mode' => 'range',
            'target_low' => 240, // 4:00 /km
            'target_high' => 270, // 4:30 /km
        ]);

        // Recovery interval
        $sampleWorkout->allSteps()->create([
            'parent_step_id' => $repeat->id,
            'sort_order' => 1,
            'step_kind' => 'recovery',
            'intensity' => 'rest',
            'duration_type' => 'time',
            'duration_value' => 120, // 2 minutes
            'target_type' => 'none',
        ]);

        // Add cooldown
        $sampleWorkout->allSteps()->create([
            'sort_order' => 2,
            'step_kind' => 'cooldown',
            'intensity' => 'cooldown',
            'duration_type' => 'time',
            'duration_value' => 600, // 10 minutes
            'target_type' => 'none',
        ]);

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
