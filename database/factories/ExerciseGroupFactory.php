<?php

namespace Database\Factories;

use App\Enums\Workout\ExerciseGroupType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ExerciseGroup>
 */
class ExerciseGroupFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'group_type' => ExerciseGroupType::Straight,
            'rounds' => 1,
            'rest_between_rounds_seconds' => null,
        ];
    }

    public function superset(): static
    {
        return $this->state(fn (): array => [
            'group_type' => ExerciseGroupType::Superset,
            'rounds' => 3,
            'rest_between_rounds_seconds' => 90,
        ]);
    }

    public function circuit(): static
    {
        return $this->state(fn (): array => [
            'group_type' => ExerciseGroupType::Circuit,
            'rounds' => 3,
            'rest_between_rounds_seconds' => 60,
        ]);
    }
}
