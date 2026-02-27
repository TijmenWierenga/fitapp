<?php

namespace Database\Factories;

use App\Models\Injury;
use App\Models\Workout;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WorkoutInjuryPainScore>
 */
class WorkoutInjuryPainScoreFactory extends Factory
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
            'pain_score' => fake()->numberBetween(1, 10),
        ];
    }
}
