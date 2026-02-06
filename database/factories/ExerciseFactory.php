<?php

namespace Database\Factories;

use App\Enums\Workout\Equipment;
use App\Enums\Workout\ExerciseCategory;
use App\Enums\Workout\MovementPattern;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Exercise>
 */
class ExerciseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'category' => ExerciseCategory::Compound,
            'equipment' => Equipment::Barbell,
            'movement_pattern' => MovementPattern::Squat,
            'primary_muscles' => ['quadriceps', 'glutes'],
            'secondary_muscles' => ['hamstrings', 'core'],
        ];
    }

    public function isolation(): static
    {
        return $this->state(fn (): array => [
            'category' => ExerciseCategory::Isolation,
            'equipment' => Equipment::Dumbbell,
            'movement_pattern' => MovementPattern::Pull,
            'primary_muscles' => ['biceps'],
            'secondary_muscles' => ['forearms'],
        ]);
    }

    public function bodyweight(): static
    {
        return $this->state(fn (): array => [
            'category' => ExerciseCategory::Compound,
            'equipment' => Equipment::Bodyweight,
            'movement_pattern' => MovementPattern::Push,
            'primary_muscles' => ['chest', 'triceps'],
            'secondary_muscles' => ['shoulders', 'core'],
        ]);
    }
}
