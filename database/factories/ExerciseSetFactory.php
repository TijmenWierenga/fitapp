<?php

namespace Database\Factories;

use App\Models\BlockExercise;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ExerciseSet>
 */
class ExerciseSetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'block_exercise_id' => BlockExercise::factory(),
            'set_number' => 1,
            'reps' => fake()->numberBetween(5, 15),
            'weight' => fake()->randomFloat(2, 20, 150),
        ];
    }

    public function cardio(): static
    {
        return $this->state(fn (array $attributes): array => [
            'reps' => null,
            'weight' => null,
            'distance' => fake()->randomFloat(2, 200, 5000),
            'duration' => fake()->numberBetween(60, 1800),
            'avg_heart_rate' => fake()->numberBetween(120, 180),
            'max_heart_rate' => fake()->numberBetween(160, 200),
            'avg_pace' => fake()->numberBetween(240, 420),
        ]);
    }
}
