<?php

namespace Database\Factories;

use App\Enums\FitnessGoal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FitnessProfile>
 */
class FitnessProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'primary_goal' => fake()->randomElement(FitnessGoal::cases()),
            'goal_details' => fake()->boolean(50) ? fake()->sentence() : null,
            'available_days_per_week' => fake()->numberBetween(1, 7),
            'minutes_per_session' => fake()->randomElement([30, 45, 60, 90, 120]),
        ];
    }

    public function weightLoss(): static
    {
        return $this->state(fn (array $attributes): array => [
            'primary_goal' => FitnessGoal::WeightLoss,
        ]);
    }

    public function muscleGain(): static
    {
        return $this->state(fn (array $attributes): array => [
            'primary_goal' => FitnessGoal::MuscleGain,
        ]);
    }

    public function endurance(): static
    {
        return $this->state(fn (array $attributes): array => [
            'primary_goal' => FitnessGoal::Endurance,
        ]);
    }

    public function generalFitness(): static
    {
        return $this->state(fn (array $attributes): array => [
            'primary_goal' => FitnessGoal::GeneralFitness,
        ]);
    }
}
