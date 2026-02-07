<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ActivityMuscleLoad;
use Illuminate\Database\Seeder;

class ActivityMuscleLoadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $loads = [
            // Run
            ['activity' => 'run', 'muscle_group' => 'calves', 'role' => 'primary', 'load_factor' => 0.8],
            ['activity' => 'run', 'muscle_group' => 'quadriceps', 'role' => 'primary', 'load_factor' => 0.7],
            ['activity' => 'run', 'muscle_group' => 'hamstrings', 'role' => 'primary', 'load_factor' => 0.6],
            ['activity' => 'run', 'muscle_group' => 'cardiovascular', 'role' => 'primary', 'load_factor' => 1.0],
            ['activity' => 'run', 'muscle_group' => 'glutes', 'role' => 'secondary', 'load_factor' => 0.5],
            ['activity' => 'run', 'muscle_group' => 'hip_flexors', 'role' => 'secondary', 'load_factor' => 0.4],
            ['activity' => 'run', 'muscle_group' => 'core', 'role' => 'secondary', 'load_factor' => 0.2],

            // Bike
            ['activity' => 'bike', 'muscle_group' => 'quadriceps', 'role' => 'primary', 'load_factor' => 0.8],
            ['activity' => 'bike', 'muscle_group' => 'cardiovascular', 'role' => 'primary', 'load_factor' => 0.9],
            ['activity' => 'bike', 'muscle_group' => 'glutes', 'role' => 'secondary', 'load_factor' => 0.6],
            ['activity' => 'bike', 'muscle_group' => 'hamstrings', 'role' => 'secondary', 'load_factor' => 0.4],
            ['activity' => 'bike', 'muscle_group' => 'calves', 'role' => 'secondary', 'load_factor' => 0.3],

            // Pool Swim
            ['activity' => 'pool_swim', 'muscle_group' => 'upper_back', 'role' => 'primary', 'load_factor' => 0.8],
            ['activity' => 'pool_swim', 'muscle_group' => 'shoulders', 'role' => 'primary', 'load_factor' => 0.7],
            ['activity' => 'pool_swim', 'muscle_group' => 'cardiovascular', 'role' => 'primary', 'load_factor' => 1.0],
            ['activity' => 'pool_swim', 'muscle_group' => 'triceps', 'role' => 'secondary', 'load_factor' => 0.5],
            ['activity' => 'pool_swim', 'muscle_group' => 'core', 'role' => 'secondary', 'load_factor' => 0.5],
            ['activity' => 'pool_swim', 'muscle_group' => 'chest', 'role' => 'secondary', 'load_factor' => 0.3],

            // Row
            ['activity' => 'row', 'muscle_group' => 'upper_back', 'role' => 'primary', 'load_factor' => 0.7],
            ['activity' => 'row', 'muscle_group' => 'quadriceps', 'role' => 'primary', 'load_factor' => 0.6],
            ['activity' => 'row', 'muscle_group' => 'cardiovascular', 'role' => 'primary', 'load_factor' => 0.9],
            ['activity' => 'row', 'muscle_group' => 'hamstrings', 'role' => 'secondary', 'load_factor' => 0.5],
            ['activity' => 'row', 'muscle_group' => 'biceps', 'role' => 'secondary', 'load_factor' => 0.5],
            ['activity' => 'row', 'muscle_group' => 'core', 'role' => 'secondary', 'load_factor' => 0.4],

            // Hike
            ['activity' => 'hike', 'muscle_group' => 'quadriceps', 'role' => 'primary', 'load_factor' => 0.5],
            ['activity' => 'hike', 'muscle_group' => 'calves', 'role' => 'primary', 'load_factor' => 0.5],
            ['activity' => 'hike', 'muscle_group' => 'cardiovascular', 'role' => 'primary', 'load_factor' => 0.7],
            ['activity' => 'hike', 'muscle_group' => 'glutes', 'role' => 'secondary', 'load_factor' => 0.4],
            ['activity' => 'hike', 'muscle_group' => 'hamstrings', 'role' => 'secondary', 'load_factor' => 0.3],
            ['activity' => 'hike', 'muscle_group' => 'core', 'role' => 'secondary', 'load_factor' => 0.2],

            // Elliptical
            ['activity' => 'elliptical', 'muscle_group' => 'quadriceps', 'role' => 'primary', 'load_factor' => 0.6],
            ['activity' => 'elliptical', 'muscle_group' => 'cardiovascular', 'role' => 'primary', 'load_factor' => 0.8],
            ['activity' => 'elliptical', 'muscle_group' => 'glutes', 'role' => 'secondary', 'load_factor' => 0.5],
            ['activity' => 'elliptical', 'muscle_group' => 'hamstrings', 'role' => 'secondary', 'load_factor' => 0.4],
            ['activity' => 'elliptical', 'muscle_group' => 'calves', 'role' => 'secondary', 'load_factor' => 0.3],
            ['activity' => 'elliptical', 'muscle_group' => 'shoulders', 'role' => 'secondary', 'load_factor' => 0.2],

            // Stair Stepper
            ['activity' => 'stair_stepper', 'muscle_group' => 'quadriceps', 'role' => 'primary', 'load_factor' => 0.7],
            ['activity' => 'stair_stepper', 'muscle_group' => 'glutes', 'role' => 'primary', 'load_factor' => 0.7],
            ['activity' => 'stair_stepper', 'muscle_group' => 'cardiovascular', 'role' => 'primary', 'load_factor' => 0.8],
            ['activity' => 'stair_stepper', 'muscle_group' => 'calves', 'role' => 'secondary', 'load_factor' => 0.5],
            ['activity' => 'stair_stepper', 'muscle_group' => 'hamstrings', 'role' => 'secondary', 'load_factor' => 0.4],
            ['activity' => 'stair_stepper', 'muscle_group' => 'core', 'role' => 'secondary', 'load_factor' => 0.2],

            // Jump Rope
            ['activity' => 'jump_rope', 'muscle_group' => 'calves', 'role' => 'primary', 'load_factor' => 0.9],
            ['activity' => 'jump_rope', 'muscle_group' => 'cardiovascular', 'role' => 'primary', 'load_factor' => 0.9],
            ['activity' => 'jump_rope', 'muscle_group' => 'quadriceps', 'role' => 'secondary', 'load_factor' => 0.4],
            ['activity' => 'jump_rope', 'muscle_group' => 'forearms', 'role' => 'secondary', 'load_factor' => 0.3],
            ['activity' => 'jump_rope', 'muscle_group' => 'shoulders', 'role' => 'secondary', 'load_factor' => 0.3],
            ['activity' => 'jump_rope', 'muscle_group' => 'core', 'role' => 'secondary', 'load_factor' => 0.2],

            // Ski
            ['activity' => 'ski', 'muscle_group' => 'quadriceps', 'role' => 'primary', 'load_factor' => 0.7],
            ['activity' => 'ski', 'muscle_group' => 'cardiovascular', 'role' => 'primary', 'load_factor' => 0.8],
            ['activity' => 'ski', 'muscle_group' => 'glutes', 'role' => 'secondary', 'load_factor' => 0.6],
            ['activity' => 'ski', 'muscle_group' => 'hamstrings', 'role' => 'secondary', 'load_factor' => 0.5],
            ['activity' => 'ski', 'muscle_group' => 'core', 'role' => 'secondary', 'load_factor' => 0.5],
            ['activity' => 'ski', 'muscle_group' => 'calves', 'role' => 'secondary', 'load_factor' => 0.4],
        ];

        ActivityMuscleLoad::query()->upsert(
            $loads,
            ['activity', 'muscle_group'],
            ['role', 'load_factor']
        );
    }
}
