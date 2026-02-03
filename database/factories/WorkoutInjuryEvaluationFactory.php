<?php

namespace Database\Factories;

use App\Models\Injury;
use App\Models\Workout;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WorkoutInjuryEvaluation>
 */
class WorkoutInjuryEvaluationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workout_id' => Workout::factory(),
            'injury_id' => Injury::factory(),
            'discomfort_score' => fake()->boolean(70) ? fake()->numberBetween(1, 10) : null,
            'notes' => fake()->boolean(50) ? fake()->sentence() : null,
        ];
    }

    public function withDiscomfort(int $score): static
    {
        return $this->state(fn (array $attributes): array => [
            'discomfort_score' => $score,
        ]);
    }

    public function withNotes(string $notes): static
    {
        return $this->state(fn (array $attributes): array => [
            'notes' => $notes,
        ]);
    }
}
