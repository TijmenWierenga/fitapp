<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Workout\Equipment;
use App\Enums\Workout\ExerciseCategory;
use App\Enums\Workout\MovementPattern;
use App\Enums\Workout\MuscleGroup;
use App\Enums\Workout\MuscleRole;
use App\Models\Exercise;
use App\Models\ExerciseMuscleLoad;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExerciseSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->seedCompoundMovements();
            $this->seedIsolationMovements();
            $this->seedBodyweightFunctional();
            $this->seedCardioConditioning();
        });
    }

    private function seedCompoundMovements(): void
    {
        $exercises = [
            [
                'name' => 'Barbell Back Squat',
                'category' => ExerciseCategory::Compound,
                'equipment' => Equipment::Barbell,
                'movement_pattern' => MovementPattern::Squat,
                'primary_muscles' => ['quadriceps', 'glutes'],
                'secondary_muscles' => ['hamstrings', 'lower_back', 'core'],
                'loads' => [
                    ['muscle_group' => MuscleGroup::Quadriceps, 'role' => MuscleRole::Primary, 'load_factor' => 1.0],
                    ['muscle_group' => MuscleGroup::Glutes, 'role' => MuscleRole::Primary, 'load_factor' => 0.9],
                    ['muscle_group' => MuscleGroup::Hamstrings, 'role' => MuscleRole::Secondary, 'load_factor' => 0.5],
                    ['muscle_group' => MuscleGroup::LowerBack, 'role' => MuscleRole::Stabilizer, 'load_factor' => 0.3],
                    ['muscle_group' => MuscleGroup::Core, 'role' => MuscleRole::Stabilizer, 'load_factor' => 0.3],
                    ['muscle_group' => MuscleGroup::Calves, 'role' => MuscleRole::Stabilizer, 'load_factor' => 0.1],
                ],
            ],
            [
                'name' => 'Front Squat',
                'category' => ExerciseCategory::Compound,
                'equipment' => Equipment::Barbell,
                'movement_pattern' => MovementPattern::Squat,
                'primary_muscles' => ['quadriceps', 'glutes'],
                'secondary_muscles' => ['core', 'upper_back'],
                'loads' => [
                    ['muscle_group' => MuscleGroup::Quadriceps, 'role' => MuscleRole::Primary, 'load_factor' => 1.0],
                    ['muscle_group' => MuscleGroup::Glutes, 'role' => MuscleRole::Primary, 'load_factor' => 0.7],
                    ['muscle_group' => MuscleGroup::Core, 'role' => MuscleRole::Secondary, 'load_factor' => 0.6],
                    ['muscle_group' => MuscleGroup::UpperBack, 'role' => MuscleRole::Stabilizer, 'load_factor' => 0.4],
                    ['muscle_group' => MuscleGroup::Hamstrings, 'role' => MuscleRole::Secondary, 'load_factor' => 0.4],
                ],
            ],
            [
                'name' => 'Deadlift',
                'category' => ExerciseCategory::Compound,
                'equipment' => Equipment::Barbell,
                'movement_pattern' => MovementPattern::Hinge,
                'primary_muscles' => ['hamstrings', 'glutes', 'lower_back'],
                'secondary_muscles' => ['upper_back', 'forearms', 'core'],
                'loads' => [
                    ['muscle_group' => MuscleGroup::Hamstrings, 'role' => MuscleRole::Primary, 'load_factor' => 1.0],
                    ['muscle_group' => MuscleGroup::Glutes, 'role' => MuscleRole::Primary, 'load_factor' => 1.0],
                    ['muscle_group' => MuscleGroup::LowerBack, 'role' => MuscleRole::Primary, 'load_factor' => 0.9],
                    ['muscle_group' => MuscleGroup::UpperBack, 'role' => MuscleRole::Secondary, 'load_factor' => 0.6],
                    ['muscle_group' => MuscleGroup::Forearms, 'role' => MuscleRole::Secondary, 'load_factor' => 0.5],
                    ['muscle_group' => MuscleGroup::Core, 'role' => MuscleRole::Stabilizer, 'load_factor' => 0.4],
                    ['muscle_group' => MuscleGroup::Quadriceps, 'role' => MuscleRole::Secondary, 'load_factor' => 0.3],
                ],
            ],
            [
                'name' => 'Romanian Deadlift',
                'category' => ExerciseCategory::Compound,
                'equipment' => Equipment::Barbell,
                'movement_pattern' => MovementPattern::Hinge,
                'primary_muscles' => ['hamstrings', 'glutes'],
                'secondary_muscles' => ['lower_back', 'forearms'],
                'loads' => [
                    ['muscle_group' => MuscleGroup::Hamstrings, 'role' => MuscleRole::Primary, 'load_factor' => 1.0],
                    ['muscle_group' => MuscleGroup::Glutes, 'role' => MuscleRole::Primary, 'load_factor' => 0.9],
                    ['muscle_group' => MuscleGroup::LowerBack, 'role' => MuscleRole::Secondary, 'load_factor' => 0.5],
                    ['muscle_group' => MuscleGroup::Forearms, 'role' => MuscleRole::Secondary, 'load_factor' => 0.4],
                    ['muscle_group' => MuscleGroup::Core, 'role' => MuscleRole::Stabilizer, 'load_factor' => 0.3],
                ],
            ],
            [
                'name' => 'Bench Press',
                'category' => ExerciseCategory::Compound,
                'equipment' => Equipment::Barbell,
                'movement_pattern' => MovementPattern::Push,
                'primary_muscles' => ['chest', 'triceps'],
                'secondary_muscles' => ['shoulders'],
                'loads' => [
                    ['muscle_group' => MuscleGroup::Chest, 'role' => MuscleRole::Primary, 'load_factor' => 1.0],
                    ['muscle_group' => MuscleGroup::Triceps, 'role' => MuscleRole::Primary, 'load_factor' => 0.7],
                    ['muscle_group' => MuscleGroup::Shoulders, 'role' => MuscleRole::Secondary, 'load_factor' => 0.5],
                    ['muscle_group' => MuscleGroup::Core, 'role' => MuscleRole::Stabilizer, 'load_factor' => 0.2],
                ],
            ],
            [
                'name' => 'Incline Bench Press',
                'category' => ExerciseCategory::Compound,
                'equipment' => Equipment::Barbell,
                'movement_pattern' => MovementPattern::Push,
                'primary_muscles' => ['chest', 'shoulders'],
                'secondary_muscles' => ['triceps'],
                'loads' => [
                    ['muscle_group' => MuscleGroup::Chest, 'role' => MuscleRole::Primary, 'load_factor' => 1.0],
                    ['muscle_group' => MuscleGroup::Shoulders, 'role' => MuscleRole::Primary, 'load_factor' => 0.7],
                    ['muscle_group' => MuscleGroup::Triceps, 'role' => MuscleRole::Secondary, 'load_factor' => 0.6],
                    ['muscle_group' => MuscleGroup::Core, 'role' => MuscleRole::Stabilizer, 'load_factor' => 0.2],
                ],
            ],
            [
                'name' => 'Overhead Press',
                'category' => ExerciseCategory::Compound,
                'equipment' => Equipment::Barbell,
                'movement_pattern' => MovementPattern::Push,
                'primary_muscles' => ['shoulders', 'triceps'],
                'secondary_muscles' => ['upper_back', 'core'],
                'loads' => [
                    ['muscle_group' => MuscleGroup::Shoulders, 'role' => MuscleRole::Primary, 'load_factor' => 1.0],
                    ['muscle_group' => MuscleGroup::Triceps, 'role' => MuscleRole::Primary, 'load_factor' => 0.6],
                    ['muscle_group' => MuscleGroup::UpperBack, 'role' => MuscleRole::Stabilizer, 'load_factor' => 0.4],
                    ['muscle_group' => MuscleGroup::Core, 'role' => MuscleRole::Stabilizer, 'load_factor' => 0.5],
                    ['muscle_group' => MuscleGroup::Chest, 'role' => MuscleRole::Secondary, 'load_factor' => 0.2],
                ],
            ],
            [
                'name' => 'Barbell Row',
                'category' => ExerciseCategory::Compound,
                'equipment' => Equipment::Barbell,
                'movement_pattern' => MovementPattern::Pull,
                'primary_muscles' => ['upper_back', 'biceps'],
                'secondary_muscles' => ['lower_back', 'forearms'],
                'loads' => [
                    ['muscle_group' => MuscleGroup::UpperBack, 'role' => MuscleRole::Primary, 'load_factor' => 1.0],
                    ['muscle_group' => MuscleGroup::Biceps, 'role' => MuscleRole::Primary, 'load_factor' => 0.6],
                    ['muscle_group' => MuscleGroup::LowerBack, 'role' => MuscleRole::Secondary, 'load_factor' => 0.5],
                    ['muscle_group' => MuscleGroup::Forearms, 'role' => MuscleRole::Secondary, 'load_factor' => 0.4],
                    ['muscle_group' => MuscleGroup::Core, 'role' => MuscleRole::Stabilizer, 'load_factor' => 0.3],
                ],
            ],
            [
                'name' => 'Pull-up',
                'category' => ExerciseCategory::Compound,
                'equipment' => Equipment::Bodyweight,
                'movement_pattern' => MovementPattern::Pull,
                'primary_muscles' => ['upper_back', 'biceps'],
                'secondary_muscles' => ['forearms', 'core'],
                'loads' => [
                    ['muscle_group' => MuscleGroup::UpperBack, 'role' => MuscleRole::Primary, 'load_factor' => 1.0],
                    ['muscle_group' => MuscleGroup::Biceps, 'role' => MuscleRole::Primary, 'load_factor' => 0.7],
                    ['muscle_group' => MuscleGroup::Forearms, 'role' => MuscleRole::Secondary, 'load_factor' => 0.5],
                    ['muscle_group' => MuscleGroup::Core, 'role' => MuscleRole::Stabilizer, 'load_factor' => 0.4],
                    ['muscle_group' => MuscleGroup::Shoulders, 'role' => MuscleRole::Secondary, 'load_factor' => 0.3],
                ],
            ],
            [
                'name' => 'Chin-up',
                'category' => ExerciseCategory::Compound,
                'equipment' => Equipment::Bodyweight,
                'movement_pattern' => MovementPattern::Pull,
                'primary_muscles' => ['biceps', 'upper_back'],
                'secondary_muscles' => ['forearms', 'core'],
                'loads' => [
                    ['muscle_group' => MuscleGroup::Biceps, 'role' => MuscleRole::Primary, 'load_factor' => 1.0],
                    ['muscle_group' => MuscleGroup::UpperBack, 'role' => MuscleRole::Primary, 'load_factor' => 0.9],
                    ['muscle_group' => MuscleGroup::Forearms, 'role' => MuscleRole::Secondary, 'load_factor' => 0.5],
                    ['muscle_group' => MuscleGroup::Core, 'role' => MuscleRole::Stabilizer, 'load_factor' => 0.4],
                    ['muscle_group' => MuscleGroup::Shoulders, 'role' => MuscleRole::Secondary, 'load_factor' => 0.3],
                ],
            ],
            [
                'name' => 'Dip',
                'category' => ExerciseCategory::Compound,
                'equipment' => Equipment::Bodyweight,
                'movement_pattern' => MovementPattern::Push,
                'primary_muscles' => ['chest', 'triceps'],
                'secondary_muscles' => ['shoulders'],
                'loads' => [
                    ['muscle_group' => MuscleGroup::Chest, 'role' => MuscleRole::Primary, 'load_factor' => 0.9],
                    ['muscle_group' => MuscleGroup::Triceps, 'role' => MuscleRole::Primary, 'load_factor' => 1.0],
                    ['muscle_group' => MuscleGroup::Shoulders, 'role' => MuscleRole::Secondary, 'load_factor' => 0.6],
                    ['muscle_group' => MuscleGroup::Core, 'role' => MuscleRole::Stabilizer, 'load_factor' => 0.3],
                ],
            ],
            [
                'name' => 'Lunge',
                'category' => ExerciseCategory::Compound,
                'equipment' => Equipment::Dumbbell,
                'movement_pattern' => MovementPattern::Squat,
                'primary_muscles' => ['quadriceps', 'glutes'],
                'secondary_muscles' => ['hamstrings', 'core'],
                'loads' => [
                    ['muscle_group' => MuscleGroup::Quadriceps, 'role' => MuscleRole::Primary, 'load_factor' => 0.9],
                    ['muscle_group' => MuscleGroup::Glutes, 'role' => MuscleRole::Primary, 'load_factor' => 0.8],
                    ['muscle_group' => MuscleGroup::Hamstrings, 'role' => MuscleRole::Secondary, 'load_factor' => 0.5],
                    ['muscle_group' => MuscleGroup::Core, 'role' => MuscleRole::Stabilizer, 'load_factor' => 0.4],
                    ['muscle_group' => MuscleGroup::Calves, 'role' => MuscleRole::Stabilizer, 'load_factor' => 0.2],
                ],
            ],
            [
                'name' => 'Bulgarian Split Squat',
                'category' => ExerciseCategory::Compound,
                'equipment' => Equipment::Dumbbell,
                'movement_pattern' => MovementPattern::Squat,
                'primary_muscles' => ['quadriceps', 'glutes'],
                'secondary_muscles' => ['hamstrings', 'core'],
                'loads' => [
                    ['muscle_group' => MuscleGroup::Quadriceps, 'role' => MuscleRole::Primary, 'load_factor' => 1.0],
                    ['muscle_group' => MuscleGroup::Glutes, 'role' => MuscleRole::Primary, 'load_factor' => 0.9],
                    ['muscle_group' => MuscleGroup::Hamstrings, 'role' => MuscleRole::Secondary, 'load_factor' => 0.5],
                    ['muscle_group' => MuscleGroup::Core, 'role' => MuscleRole::Stabilizer, 'load_factor' => 0.5],
                    ['muscle_group' => MuscleGroup::Calves, 'role' => MuscleRole::Stabilizer, 'load_factor' => 0.2],
                ],
            ],
            [
                'name' => 'Hip Thrust',
                'category' => ExerciseCategory::Compound,
                'equipment' => Equipment::Barbell,
                'movement_pattern' => MovementPattern::Hinge,
                'primary_muscles' => ['glutes', 'hamstrings'],
                'secondary_muscles' => ['core', 'lower_back'],
                'loads' => [
                    ['muscle_group' => MuscleGroup::Glutes, 'role' => MuscleRole::Primary, 'load_factor' => 1.0],
                    ['muscle_group' => MuscleGroup::Hamstrings, 'role' => MuscleRole::Primary, 'load_factor' => 0.6],
                    ['muscle_group' => MuscleGroup::Core, 'role' => MuscleRole::Secondary, 'load_factor' => 0.4],
                    ['muscle_group' => MuscleGroup::LowerBack, 'role' => MuscleRole::Stabilizer, 'load_factor' => 0.2],
                ],
            ],
            [
                'name' => 'Clean & Press',
                'category' => ExerciseCategory::Compound,
                'equipment' => Equipment::Barbell,
                'movement_pattern' => MovementPattern::Other,
                'primary_muscles' => ['shoulders', 'quadriceps', 'glutes'],
                'secondary_muscles' => ['hamstrings', 'upper_back', 'triceps', 'core'],
                'loads' => [
                    ['muscle_group' => MuscleGroup::Shoulders, 'role' => MuscleRole::Primary, 'load_factor' => 1.0],
                    ['muscle_group' => MuscleGroup::Quadriceps, 'role' => MuscleRole::Primary, 'load_factor' => 0.8],
                    ['muscle_group' => MuscleGroup::Glutes, 'role' => MuscleRole::Primary, 'load_factor' => 0.7],
                    ['muscle_group' => MuscleGroup::Hamstrings, 'role' => MuscleRole::Secondary, 'load_factor' => 0.6],
                    ['muscle_group' => MuscleGroup::UpperBack, 'role' => MuscleRole::Secondary, 'load_factor' => 0.5],
                    ['muscle_group' => MuscleGroup::Triceps, 'role' => MuscleRole::Secondary, 'load_factor' => 0.5],
                    ['muscle_group' => MuscleGroup::Core, 'role' => MuscleRole::Stabilizer, 'load_factor' => 0.6],
                ],
            ],
        ];

        $this->createExercises($exercises);
    }

    private function seedIsolationMovements(): void
    {
        $exercises = [
            [
                'name' => 'Bicep Curl',
                'category' => ExerciseCategory::Isolation,
                'equipment' => Equipment::Dumbbell,
                'movement_pattern' => MovementPattern::Pull,
                'primary_muscles' => ['biceps'],
                'secondary_muscles' => ['forearms'],
                'loads' => [
                    ['muscle_group' => MuscleGroup::Biceps, 'role' => MuscleRole::Primary, 'load_factor' => 1.0],
                    ['muscle_group' => MuscleGroup::Forearms, 'role' => MuscleRole::Secondary, 'load_factor' => 0.3],
                ],
            ],
            [
                'name' => 'Hammer Curl',
                'category' => ExerciseCategory::Isolation,
                'equipment' => Equipment::Dumbbell,
                'movement_pattern' => MovementPattern::Pull,
                'primary_muscles' => ['biceps', 'forearms'],
                'secondary_muscles' => [],
                'loads' => [
                    ['muscle_group' => MuscleGroup::Biceps, 'role' => MuscleRole::Primary, 'load_factor' => 0.9],
                    ['muscle_group' => MuscleGroup::Forearms, 'role' => MuscleRole::Primary, 'load_factor' => 0.7],
                ],
            ],
            [
                'name' => 'Tricep Extension',
                'category' => ExerciseCategory::Isolation,
                'equipment' => Equipment::Dumbbell,
                'movement_pattern' => MovementPattern::Push,
                'primary_muscles' => ['triceps'],
                'secondary_muscles' => [],
                'loads' => [
                    ['muscle_group' => MuscleGroup::Triceps, 'role' => MuscleRole::Primary, 'load_factor' => 1.0],
                    ['muscle_group' => MuscleGroup::Core, 'role' => MuscleRole::Stabilizer, 'load_factor' => 0.2],
                ],
            ],
            [
                'name' => 'Tricep Pushdown',
                'category' => ExerciseCategory::Isolation,
                'equipment' => Equipment::Cable,
                'movement_pattern' => MovementPattern::Push,
                'primary_muscles' => ['triceps'],
                'secondary_muscles' => [],
                'loads' => [
                    ['muscle_group' => MuscleGroup::Triceps, 'role' => MuscleRole::Primary, 'load_factor' => 1.0],
                    ['muscle_group' => MuscleGroup::Core, 'role' => MuscleRole::Stabilizer, 'load_factor' => 0.1],
                ],
            ],
            [
                'name' => 'Lateral Raise',
                'category' => ExerciseCategory::Isolation,
                'equipment' => Equipment::Dumbbell,
                'movement_pattern' => MovementPattern::Push,
                'primary_muscles' => ['shoulders'],
                'secondary_muscles' => [],
                'loads' => [
                    ['muscle_group' => MuscleGroup::Shoulders, 'role' => MuscleRole::Primary, 'load_factor' => 1.0],
                    ['muscle_group' => MuscleGroup::Core, 'role' => MuscleRole::Stabilizer, 'load_factor' => 0.2],
                ],
            ],
            [
                'name' => 'Front Raise',
                'category' => ExerciseCategory::Isolation,
                'equipment' => Equipment::Dumbbell,
                'movement_pattern' => MovementPattern::Push,
                'primary_muscles' => ['shoulders'],
                'secondary_muscles' => ['chest'],
                'loads' => [
                    ['muscle_group' => MuscleGroup::Shoulders, 'role' => MuscleRole::Primary, 'load_factor' => 1.0],
                    ['muscle_group' => MuscleGroup::Chest, 'role' => MuscleRole::Secondary, 'load_factor' => 0.3],
                    ['muscle_group' => MuscleGroup::Core, 'role' => MuscleRole::Stabilizer, 'load_factor' => 0.2],
                ],
            ],
            [
                'name' => 'Rear Delt Fly',
                'category' => ExerciseCategory::Isolation,
                'equipment' => Equipment::Dumbbell,
                'movement_pattern' => MovementPattern::Pull,
                'primary_muscles' => ['shoulders', 'upper_back'],
                'secondary_muscles' => [],
                'loads' => [
                    ['muscle_group' => MuscleGroup::Shoulders, 'role' => MuscleRole::Primary, 'load_factor' => 1.0],
                    ['muscle_group' => MuscleGroup::UpperBack, 'role' => MuscleRole::Primary, 'load_factor' => 0.6],
                    ['muscle_group' => MuscleGroup::Core, 'role' => MuscleRole::Stabilizer, 'load_factor' => 0.2],
                ],
            ],
            [
                'name' => 'Leg Curl',
                'category' => ExerciseCategory::Isolation,
                'equipment' => Equipment::Machine,
                'movement_pattern' => MovementPattern::Hinge,
                'primary_muscles' => ['hamstrings'],
                'secondary_muscles' => [],
                'loads' => [
                    ['muscle_group' => MuscleGroup::Hamstrings, 'role' => MuscleRole::Primary, 'load_factor' => 1.0],
                    ['muscle_group' => MuscleGroup::Calves, 'role' => MuscleRole::Stabilizer, 'load_factor' => 0.1],
                ],
            ],
            [
                'name' => 'Leg Extension',
                'category' => ExerciseCategory::Isolation,
                'equipment' => Equipment::Machine,
                'movement_pattern' => MovementPattern::Squat,
                'primary_muscles' => ['quadriceps'],
                'secondary_muscles' => [],
                'loads' => [
                    ['muscle_group' => MuscleGroup::Quadriceps, 'role' => MuscleRole::Primary, 'load_factor' => 1.0],
                ],
            ],
            [
                'name' => 'Calf Raise',
                'category' => ExerciseCategory::Isolation,
                'equipment' => Equipment::Machine,
                'movement_pattern' => MovementPattern::Other,
                'primary_muscles' => ['calves'],
                'secondary_muscles' => [],
                'loads' => [
                    ['muscle_group' => MuscleGroup::Calves, 'role' => MuscleRole::Primary, 'load_factor' => 1.0],
                ],
            ],
            [
                'name' => 'Face Pull',
                'category' => ExerciseCategory::Isolation,
                'equipment' => Equipment::Cable,
                'movement_pattern' => MovementPattern::Pull,
                'primary_muscles' => ['upper_back', 'shoulders'],
                'secondary_muscles' => ['biceps'],
                'loads' => [
                    ['muscle_group' => MuscleGroup::UpperBack, 'role' => MuscleRole::Primary, 'load_factor' => 0.8],
                    ['muscle_group' => MuscleGroup::Shoulders, 'role' => MuscleRole::Primary, 'load_factor' => 0.8],
                    ['muscle_group' => MuscleGroup::Biceps, 'role' => MuscleRole::Secondary, 'load_factor' => 0.3],
                ],
            ],
            [
                'name' => 'Chest Fly',
                'category' => ExerciseCategory::Isolation,
                'equipment' => Equipment::Dumbbell,
                'movement_pattern' => MovementPattern::Push,
                'primary_muscles' => ['chest'],
                'secondary_muscles' => ['shoulders'],
                'loads' => [
                    ['muscle_group' => MuscleGroup::Chest, 'role' => MuscleRole::Primary, 'load_factor' => 1.0],
                    ['muscle_group' => MuscleGroup::Shoulders, 'role' => MuscleRole::Secondary, 'load_factor' => 0.4],
                    ['muscle_group' => MuscleGroup::Biceps, 'role' => MuscleRole::Stabilizer, 'load_factor' => 0.2],
                ],
            ],
            [
                'name' => 'Cable Crossover',
                'category' => ExerciseCategory::Isolation,
                'equipment' => Equipment::Cable,
                'movement_pattern' => MovementPattern::Push,
                'primary_muscles' => ['chest'],
                'secondary_muscles' => ['shoulders'],
                'loads' => [
                    ['muscle_group' => MuscleGroup::Chest, 'role' => MuscleRole::Primary, 'load_factor' => 1.0],
                    ['muscle_group' => MuscleGroup::Shoulders, 'role' => MuscleRole::Secondary, 'load_factor' => 0.3],
                    ['muscle_group' => MuscleGroup::Core, 'role' => MuscleRole::Stabilizer, 'load_factor' => 0.2],
                ],
            ],
            [
                'name' => 'Preacher Curl',
                'category' => ExerciseCategory::Isolation,
                'equipment' => Equipment::Barbell,
                'movement_pattern' => MovementPattern::Pull,
                'primary_muscles' => ['biceps'],
                'secondary_muscles' => ['forearms'],
                'loads' => [
                    ['muscle_group' => MuscleGroup::Biceps, 'role' => MuscleRole::Primary, 'load_factor' => 1.0],
                    ['muscle_group' => MuscleGroup::Forearms, 'role' => MuscleRole::Secondary, 'load_factor' => 0.3],
                ],
            ],
            [
                'name' => 'Wrist Curl',
                'category' => ExerciseCategory::Isolation,
                'equipment' => Equipment::Dumbbell,
                'movement_pattern' => MovementPattern::Pull,
                'primary_muscles' => ['forearms'],
                'secondary_muscles' => [],
                'loads' => [
                    ['muscle_group' => MuscleGroup::Forearms, 'role' => MuscleRole::Primary, 'load_factor' => 1.0],
                ],
            ],
        ];

        $this->createExercises($exercises);
    }

    private function seedBodyweightFunctional(): void
    {
        $exercises = [
            [
                'name' => 'Push-up',
                'category' => ExerciseCategory::Compound,
                'equipment' => Equipment::Bodyweight,
                'movement_pattern' => MovementPattern::Push,
                'primary_muscles' => ['chest', 'triceps'],
                'secondary_muscles' => ['shoulders', 'core'],
                'loads' => [
                    ['muscle_group' => MuscleGroup::Chest, 'role' => MuscleRole::Primary, 'load_factor' => 0.9],
                    ['muscle_group' => MuscleGroup::Triceps, 'role' => MuscleRole::Primary, 'load_factor' => 0.7],
                    ['muscle_group' => MuscleGroup::Shoulders, 'role' => MuscleRole::Secondary, 'load_factor' => 0.5],
                    ['muscle_group' => MuscleGroup::Core, 'role' => MuscleRole::Stabilizer, 'load_factor' => 0.4],
                ],
            ],
            [
                'name' => 'Plank',
                'category' => ExerciseCategory::Isolation,
                'equipment' => Equipment::Bodyweight,
                'movement_pattern' => MovementPattern::Core,
                'primary_muscles' => ['core'],
                'secondary_muscles' => ['shoulders', 'glutes'],
                'loads' => [
                    ['muscle_group' => MuscleGroup::Core, 'role' => MuscleRole::Primary, 'load_factor' => 1.0],
                    ['muscle_group' => MuscleGroup::Shoulders, 'role' => MuscleRole::Stabilizer, 'load_factor' => 0.4],
                    ['muscle_group' => MuscleGroup::Glutes, 'role' => MuscleRole::Stabilizer, 'load_factor' => 0.3],
                ],
            ],
            [
                'name' => 'Side Plank',
                'category' => ExerciseCategory::Isolation,
                'equipment' => Equipment::Bodyweight,
                'movement_pattern' => MovementPattern::Core,
                'primary_muscles' => ['core'],
                'secondary_muscles' => ['shoulders'],
                'loads' => [
                    ['muscle_group' => MuscleGroup::Core, 'role' => MuscleRole::Primary, 'load_factor' => 1.0],
                    ['muscle_group' => MuscleGroup::Shoulders, 'role' => MuscleRole::Stabilizer, 'load_factor' => 0.5],
                    ['muscle_group' => MuscleGroup::Glutes, 'role' => MuscleRole::Stabilizer, 'load_factor' => 0.4],
                ],
            ],
            [
                'name' => 'Mountain Climber',
                'category' => ExerciseCategory::Cardio,
                'equipment' => Equipment::Bodyweight,
                'movement_pattern' => MovementPattern::Core,
                'primary_muscles' => ['core', 'cardiovascular'],
                'secondary_muscles' => ['shoulders', 'hip_flexors'],
                'loads' => [
                    ['muscle_group' => MuscleGroup::Core, 'role' => MuscleRole::Primary, 'load_factor' => 0.8],
                    ['muscle_group' => MuscleGroup::Cardiovascular, 'role' => MuscleRole::Primary, 'load_factor' => 0.9],
                    ['muscle_group' => MuscleGroup::Shoulders, 'role' => MuscleRole::Secondary, 'load_factor' => 0.5],
                    ['muscle_group' => MuscleGroup::HipFlexors, 'role' => MuscleRole::Secondary, 'load_factor' => 0.6],
                ],
            ],
            [
                'name' => 'Burpee',
                'category' => ExerciseCategory::Cardio,
                'equipment' => Equipment::Bodyweight,
                'movement_pattern' => MovementPattern::Other,
                'primary_muscles' => ['cardiovascular', 'chest', 'quadriceps'],
                'secondary_muscles' => ['shoulders', 'core', 'triceps'],
                'loads' => [
                    ['muscle_group' => MuscleGroup::Cardiovascular, 'role' => MuscleRole::Primary, 'load_factor' => 1.0],
                    ['muscle_group' => MuscleGroup::Chest, 'role' => MuscleRole::Primary, 'load_factor' => 0.6],
                    ['muscle_group' => MuscleGroup::Quadriceps, 'role' => MuscleRole::Primary, 'load_factor' => 0.7],
                    ['muscle_group' => MuscleGroup::Shoulders, 'role' => MuscleRole::Secondary, 'load_factor' => 0.5],
                    ['muscle_group' => MuscleGroup::Core, 'role' => MuscleRole::Secondary, 'load_factor' => 0.6],
                    ['muscle_group' => MuscleGroup::Triceps, 'role' => MuscleRole::Secondary, 'load_factor' => 0.4],
                ],
            ],
            [
                'name' => 'Box Jump',
                'category' => ExerciseCategory::Cardio,
                'equipment' => Equipment::Other,
                'movement_pattern' => MovementPattern::Squat,
                'primary_muscles' => ['quadriceps', 'glutes', 'calves'],
                'secondary_muscles' => ['hamstrings', 'cardiovascular'],
                'loads' => [
                    ['muscle_group' => MuscleGroup::Quadriceps, 'role' => MuscleRole::Primary, 'load_factor' => 0.9],
                    ['muscle_group' => MuscleGroup::Glutes, 'role' => MuscleRole::Primary, 'load_factor' => 0.8],
                    ['muscle_group' => MuscleGroup::Calves, 'role' => MuscleRole::Primary, 'load_factor' => 0.7],
                    ['muscle_group' => MuscleGroup::Hamstrings, 'role' => MuscleRole::Secondary, 'load_factor' => 0.5],
                    ['muscle_group' => MuscleGroup::Cardiovascular, 'role' => MuscleRole::Secondary, 'load_factor' => 0.6],
                ],
            ],
            [
                'name' => 'Kettlebell Swing',
                'category' => ExerciseCategory::Compound,
                'equipment' => Equipment::Kettlebell,
                'movement_pattern' => MovementPattern::Hinge,
                'primary_muscles' => ['glutes', 'hamstrings'],
                'secondary_muscles' => ['lower_back', 'shoulders', 'core'],
                'loads' => [
                    ['muscle_group' => MuscleGroup::Glutes, 'role' => MuscleRole::Primary, 'load_factor' => 1.0],
                    ['muscle_group' => MuscleGroup::Hamstrings, 'role' => MuscleRole::Primary, 'load_factor' => 0.9],
                    ['muscle_group' => MuscleGroup::LowerBack, 'role' => MuscleRole::Secondary, 'load_factor' => 0.5],
                    ['muscle_group' => MuscleGroup::Shoulders, 'role' => MuscleRole::Secondary, 'load_factor' => 0.4],
                    ['muscle_group' => MuscleGroup::Core, 'role' => MuscleRole::Stabilizer, 'load_factor' => 0.5],
                    ['muscle_group' => MuscleGroup::Cardiovascular, 'role' => MuscleRole::Secondary, 'load_factor' => 0.6],
                ],
            ],
            [
                'name' => 'Goblet Squat',
                'category' => ExerciseCategory::Compound,
                'equipment' => Equipment::Kettlebell,
                'movement_pattern' => MovementPattern::Squat,
                'primary_muscles' => ['quadriceps', 'glutes'],
                'secondary_muscles' => ['core', 'upper_back'],
                'loads' => [
                    ['muscle_group' => MuscleGroup::Quadriceps, 'role' => MuscleRole::Primary, 'load_factor' => 0.9],
                    ['muscle_group' => MuscleGroup::Glutes, 'role' => MuscleRole::Primary, 'load_factor' => 0.8],
                    ['muscle_group' => MuscleGroup::Core, 'role' => MuscleRole::Secondary, 'load_factor' => 0.5],
                    ['muscle_group' => MuscleGroup::UpperBack, 'role' => MuscleRole::Stabilizer, 'load_factor' => 0.3],
                    ['muscle_group' => MuscleGroup::Hamstrings, 'role' => MuscleRole::Secondary, 'load_factor' => 0.4],
                ],
            ],
            [
                'name' => 'Turkish Get-up',
                'category' => ExerciseCategory::Compound,
                'equipment' => Equipment::Kettlebell,
                'movement_pattern' => MovementPattern::Other,
                'primary_muscles' => ['core', 'shoulders'],
                'secondary_muscles' => ['glutes', 'quadriceps', 'triceps'],
                'loads' => [
                    ['muscle_group' => MuscleGroup::Core, 'role' => MuscleRole::Primary, 'load_factor' => 1.0],
                    ['muscle_group' => MuscleGroup::Shoulders, 'role' => MuscleRole::Primary, 'load_factor' => 0.9],
                    ['muscle_group' => MuscleGroup::Glutes, 'role' => MuscleRole::Secondary, 'load_factor' => 0.6],
                    ['muscle_group' => MuscleGroup::Quadriceps, 'role' => MuscleRole::Secondary, 'load_factor' => 0.5],
                    ['muscle_group' => MuscleGroup::Triceps, 'role' => MuscleRole::Secondary, 'load_factor' => 0.5],
                ],
            ],
            [
                'name' => 'Hanging Leg Raise',
                'category' => ExerciseCategory::Isolation,
                'equipment' => Equipment::Bodyweight,
                'movement_pattern' => MovementPattern::Core,
                'primary_muscles' => ['core', 'hip_flexors'],
                'secondary_muscles' => ['forearms'],
                'loads' => [
                    ['muscle_group' => MuscleGroup::Core, 'role' => MuscleRole::Primary, 'load_factor' => 1.0],
                    ['muscle_group' => MuscleGroup::HipFlexors, 'role' => MuscleRole::Primary, 'load_factor' => 0.8],
                    ['muscle_group' => MuscleGroup::Forearms, 'role' => MuscleRole::Secondary, 'load_factor' => 0.5],
                ],
            ],
            [
                'name' => 'Ab Wheel Rollout',
                'category' => ExerciseCategory::Isolation,
                'equipment' => Equipment::Other,
                'movement_pattern' => MovementPattern::Core,
                'primary_muscles' => ['core'],
                'secondary_muscles' => ['shoulders', 'upper_back'],
                'loads' => [
                    ['muscle_group' => MuscleGroup::Core, 'role' => MuscleRole::Primary, 'load_factor' => 1.0],
                    ['muscle_group' => MuscleGroup::Shoulders, 'role' => MuscleRole::Secondary, 'load_factor' => 0.5],
                    ['muscle_group' => MuscleGroup::UpperBack, 'role' => MuscleRole::Stabilizer, 'load_factor' => 0.3],
                ],
            ],
            [
                'name' => 'Inverted Row',
                'category' => ExerciseCategory::Compound,
                'equipment' => Equipment::Bodyweight,
                'movement_pattern' => MovementPattern::Pull,
                'primary_muscles' => ['upper_back', 'biceps'],
                'secondary_muscles' => ['core', 'forearms'],
                'loads' => [
                    ['muscle_group' => MuscleGroup::UpperBack, 'role' => MuscleRole::Primary, 'load_factor' => 0.9],
                    ['muscle_group' => MuscleGroup::Biceps, 'role' => MuscleRole::Primary, 'load_factor' => 0.6],
                    ['muscle_group' => MuscleGroup::Core, 'role' => MuscleRole::Stabilizer, 'load_factor' => 0.5],
                    ['muscle_group' => MuscleGroup::Forearms, 'role' => MuscleRole::Secondary, 'load_factor' => 0.4],
                ],
            ],
            [
                'name' => 'Pike Push-up',
                'category' => ExerciseCategory::Compound,
                'equipment' => Equipment::Bodyweight,
                'movement_pattern' => MovementPattern::Push,
                'primary_muscles' => ['shoulders', 'triceps'],
                'secondary_muscles' => ['chest', 'core'],
                'loads' => [
                    ['muscle_group' => MuscleGroup::Shoulders, 'role' => MuscleRole::Primary, 'load_factor' => 1.0],
                    ['muscle_group' => MuscleGroup::Triceps, 'role' => MuscleRole::Primary, 'load_factor' => 0.6],
                    ['muscle_group' => MuscleGroup::Chest, 'role' => MuscleRole::Secondary, 'load_factor' => 0.3],
                    ['muscle_group' => MuscleGroup::Core, 'role' => MuscleRole::Stabilizer, 'load_factor' => 0.4],
                ],
            ],
            [
                'name' => 'Glute Bridge',
                'category' => ExerciseCategory::Isolation,
                'equipment' => Equipment::Bodyweight,
                'movement_pattern' => MovementPattern::Hinge,
                'primary_muscles' => ['glutes', 'hamstrings'],
                'secondary_muscles' => ['core'],
                'loads' => [
                    ['muscle_group' => MuscleGroup::Glutes, 'role' => MuscleRole::Primary, 'load_factor' => 1.0],
                    ['muscle_group' => MuscleGroup::Hamstrings, 'role' => MuscleRole::Primary, 'load_factor' => 0.6],
                    ['muscle_group' => MuscleGroup::Core, 'role' => MuscleRole::Stabilizer, 'load_factor' => 0.3],
                ],
            ],
            [
                'name' => 'Dead Bug',
                'category' => ExerciseCategory::Isolation,
                'equipment' => Equipment::Bodyweight,
                'movement_pattern' => MovementPattern::Core,
                'primary_muscles' => ['core'],
                'secondary_muscles' => ['hip_flexors'],
                'loads' => [
                    ['muscle_group' => MuscleGroup::Core, 'role' => MuscleRole::Primary, 'load_factor' => 1.0],
                    ['muscle_group' => MuscleGroup::HipFlexors, 'role' => MuscleRole::Secondary, 'load_factor' => 0.5],
                ],
            ],
        ];

        $this->createExercises($exercises);
    }

    private function seedCardioConditioning(): void
    {
        $exercises = [
            [
                'name' => 'Battle Ropes',
                'category' => ExerciseCategory::Cardio,
                'equipment' => Equipment::Other,
                'movement_pattern' => MovementPattern::Other,
                'primary_muscles' => ['shoulders', 'cardiovascular'],
                'secondary_muscles' => ['core', 'upper_back', 'forearms'],
                'loads' => [
                    ['muscle_group' => MuscleGroup::Shoulders, 'role' => MuscleRole::Primary, 'load_factor' => 0.9],
                    ['muscle_group' => MuscleGroup::Cardiovascular, 'role' => MuscleRole::Primary, 'load_factor' => 1.0],
                    ['muscle_group' => MuscleGroup::Core, 'role' => MuscleRole::Secondary, 'load_factor' => 0.6],
                    ['muscle_group' => MuscleGroup::UpperBack, 'role' => MuscleRole::Secondary, 'load_factor' => 0.5],
                    ['muscle_group' => MuscleGroup::Forearms, 'role' => MuscleRole::Secondary, 'load_factor' => 0.5],
                ],
            ],
            [
                'name' => 'Sled Push',
                'category' => ExerciseCategory::Cardio,
                'equipment' => Equipment::Other,
                'movement_pattern' => MovementPattern::Push,
                'primary_muscles' => ['quadriceps', 'glutes', 'cardiovascular'],
                'secondary_muscles' => ['calves', 'core', 'chest', 'triceps'],
                'loads' => [
                    ['muscle_group' => MuscleGroup::Quadriceps, 'role' => MuscleRole::Primary, 'load_factor' => 1.0],
                    ['muscle_group' => MuscleGroup::Glutes, 'role' => MuscleRole::Primary, 'load_factor' => 0.9],
                    ['muscle_group' => MuscleGroup::Cardiovascular, 'role' => MuscleRole::Primary, 'load_factor' => 0.9],
                    ['muscle_group' => MuscleGroup::Calves, 'role' => MuscleRole::Secondary, 'load_factor' => 0.6],
                    ['muscle_group' => MuscleGroup::Core, 'role' => MuscleRole::Secondary, 'load_factor' => 0.6],
                    ['muscle_group' => MuscleGroup::Chest, 'role' => MuscleRole::Secondary, 'load_factor' => 0.4],
                    ['muscle_group' => MuscleGroup::Triceps, 'role' => MuscleRole::Secondary, 'load_factor' => 0.4],
                ],
            ],
            [
                'name' => 'Sled Pull',
                'category' => ExerciseCategory::Cardio,
                'equipment' => Equipment::Other,
                'movement_pattern' => MovementPattern::Pull,
                'primary_muscles' => ['upper_back', 'quadriceps', 'cardiovascular'],
                'secondary_muscles' => ['glutes', 'hamstrings', 'biceps', 'core'],
                'loads' => [
                    ['muscle_group' => MuscleGroup::UpperBack, 'role' => MuscleRole::Primary, 'load_factor' => 0.9],
                    ['muscle_group' => MuscleGroup::Quadriceps, 'role' => MuscleRole::Primary, 'load_factor' => 0.8],
                    ['muscle_group' => MuscleGroup::Cardiovascular, 'role' => MuscleRole::Primary, 'load_factor' => 0.9],
                    ['muscle_group' => MuscleGroup::Glutes, 'role' => MuscleRole::Secondary, 'load_factor' => 0.6],
                    ['muscle_group' => MuscleGroup::Hamstrings, 'role' => MuscleRole::Secondary, 'load_factor' => 0.5],
                    ['muscle_group' => MuscleGroup::Biceps, 'role' => MuscleRole::Secondary, 'load_factor' => 0.5],
                    ['muscle_group' => MuscleGroup::Core, 'role' => MuscleRole::Stabilizer, 'load_factor' => 0.5],
                ],
            ],
            [
                'name' => 'Farmer\'s Walk',
                'category' => ExerciseCategory::Cardio,
                'equipment' => Equipment::Dumbbell,
                'movement_pattern' => MovementPattern::Carry,
                'primary_muscles' => ['forearms', 'upper_back'],
                'secondary_muscles' => ['core', 'quadriceps', 'calves', 'cardiovascular'],
                'loads' => [
                    ['muscle_group' => MuscleGroup::Forearms, 'role' => MuscleRole::Primary, 'load_factor' => 1.0],
                    ['muscle_group' => MuscleGroup::UpperBack, 'role' => MuscleRole::Primary, 'load_factor' => 0.7],
                    ['muscle_group' => MuscleGroup::Core, 'role' => MuscleRole::Secondary, 'load_factor' => 0.6],
                    ['muscle_group' => MuscleGroup::Quadriceps, 'role' => MuscleRole::Secondary, 'load_factor' => 0.4],
                    ['muscle_group' => MuscleGroup::Calves, 'role' => MuscleRole::Secondary, 'load_factor' => 0.3],
                    ['muscle_group' => MuscleGroup::Cardiovascular, 'role' => MuscleRole::Secondary, 'load_factor' => 0.5],
                ],
            ],
            [
                'name' => 'Bear Crawl',
                'category' => ExerciseCategory::Cardio,
                'equipment' => Equipment::Bodyweight,
                'movement_pattern' => MovementPattern::Other,
                'primary_muscles' => ['core', 'shoulders', 'cardiovascular'],
                'secondary_muscles' => ['quadriceps', 'triceps', 'chest'],
                'loads' => [
                    ['muscle_group' => MuscleGroup::Core, 'role' => MuscleRole::Primary, 'load_factor' => 0.9],
                    ['muscle_group' => MuscleGroup::Shoulders, 'role' => MuscleRole::Primary, 'load_factor' => 0.8],
                    ['muscle_group' => MuscleGroup::Cardiovascular, 'role' => MuscleRole::Primary, 'load_factor' => 0.8],
                    ['muscle_group' => MuscleGroup::Quadriceps, 'role' => MuscleRole::Secondary, 'load_factor' => 0.6],
                    ['muscle_group' => MuscleGroup::Triceps, 'role' => MuscleRole::Secondary, 'load_factor' => 0.5],
                    ['muscle_group' => MuscleGroup::Chest, 'role' => MuscleRole::Secondary, 'load_factor' => 0.4],
                ],
            ],
        ];

        $this->createExercises($exercises);
    }

    /**
     * @param  array<int, array{
     *     name: string,
     *     category: ExerciseCategory,
     *     equipment: Equipment,
     *     movement_pattern: MovementPattern,
     *     primary_muscles: array<int, string>,
     *     secondary_muscles: array<int, string>,
     *     loads: array<int, array{muscle_group: MuscleGroup, role: MuscleRole, load_factor: float}>
     * }>  $exercises
     */
    private function createExercises(array $exercises): void
    {
        foreach ($exercises as $exerciseData) {
            $exercise = Exercise::query()->firstOrCreate(
                ['name' => $exerciseData['name']],
                [
                    'category' => $exerciseData['category'],
                    'equipment' => $exerciseData['equipment'],
                    'movement_pattern' => $exerciseData['movement_pattern'],
                    'primary_muscles' => $exerciseData['primary_muscles'],
                    'secondary_muscles' => $exerciseData['secondary_muscles'],
                ]
            );

            foreach ($exerciseData['loads'] as $load) {
                ExerciseMuscleLoad::query()->updateOrCreate(
                    [
                        'exercise_id' => $exercise->id,
                        'muscle_group' => $load['muscle_group'],
                    ],
                    [
                        'role' => $load['role'],
                        'load_factor' => $load['load_factor'],
                    ]
                );
            }
        }
    }
}
