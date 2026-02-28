<?php

namespace Database\Factories;

use App\Models\Injury;
use App\Models\Workout;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WorkoutPainScore>
 */
class WorkoutPainScoreFactory extends Factory
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
            'pain_score' => fake()->numberBetween(0, 10),
        ];
    }

    public function noPain(): static
    {
        return $this->state(fn (array $attributes): array => [
            'pain_score' => 0,
        ]);
    }

    public function mildPain(): static
    {
        return $this->state(fn (array $attributes): array => [
            'pain_score' => fake()->numberBetween(1, 3),
        ]);
    }

    public function moderatePain(): static
    {
        return $this->state(fn (array $attributes): array => [
            'pain_score' => fake()->numberBetween(4, 6),
        ]);
    }

    public function severePain(): static
    {
        return $this->state(fn (array $attributes): array => [
            'pain_score' => fake()->numberBetween(7, 10),
        ]);
    }
}
