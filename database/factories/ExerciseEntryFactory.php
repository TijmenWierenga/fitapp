<?php

namespace Database\Factories;

use App\Models\Exercise;
use App\Models\ExerciseGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ExerciseEntry>
 */
class ExerciseEntryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'exercise_group_id' => ExerciseGroup::factory(),
            'exercise_id' => Exercise::factory(),
            'position' => 0,
            'sets' => 3,
            'reps' => 10,
            'duration_seconds' => null,
            'weight_kg' => null,
            'rpe_target' => null,
            'rest_between_sets_seconds' => 90,
            'notes' => null,
        ];
    }

    public function timed(int $seconds = 30): static
    {
        return $this->state(fn (): array => [
            'reps' => null,
            'duration_seconds' => $seconds,
        ]);
    }

    public function weighted(float $kg = 60.0): static
    {
        return $this->state(fn (): array => [
            'weight_kg' => $kg,
        ]);
    }
}
