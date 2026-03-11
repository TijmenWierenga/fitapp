<?php

namespace Database\Factories;

use App\Enums\BiologicalSex;
use App\Enums\Equipment;
use App\Enums\ExperienceLevel;
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
            'prefer_garmin_exercises' => false,
            'experience_level' => fake()->randomElement(ExperienceLevel::cases()),
            'date_of_birth' => fake()->dateTimeBetween('-60 years', '-18 years'),
            'biological_sex' => fake()->randomElement(BiologicalSex::cases()),
            'body_weight_kg' => fake()->randomFloat(2, 45, 130),
            'height_cm' => fake()->numberBetween(150, 200),
            'has_gym_access' => fake()->boolean(),
            'home_equipment' => fake()->randomElements(
                array_map(fn (Equipment $e): string => $e->value, Equipment::homeEquipmentOptions()),
                fake()->numberBetween(0, 4),
            ),
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

    public function preferGarmin(): static
    {
        return $this->state(fn (array $attributes): array => [
            'prefer_garmin_exercises' => true,
        ]);
    }

    public function beginner(): static
    {
        return $this->state(fn (array $attributes): array => [
            'experience_level' => ExperienceLevel::Beginner,
        ]);
    }

    public function intermediate(): static
    {
        return $this->state(fn (array $attributes): array => [
            'experience_level' => ExperienceLevel::Intermediate,
        ]);
    }

    public function advanced(): static
    {
        return $this->state(fn (array $attributes): array => [
            'experience_level' => ExperienceLevel::Advanced,
        ]);
    }

    public function male(): static
    {
        return $this->state(fn (array $attributes): array => [
            'biological_sex' => BiologicalSex::Male,
        ]);
    }

    public function female(): static
    {
        return $this->state(fn (array $attributes): array => [
            'biological_sex' => BiologicalSex::Female,
        ]);
    }

    /**
     * @param  array<Equipment>  $equipment
     */
    public function withEquipment(array $equipment): static
    {
        return $this->state(fn (array $attributes): array => [
            'home_equipment' => array_map(fn (Equipment $e): string => $e->value, $equipment),
        ]);
    }
}
