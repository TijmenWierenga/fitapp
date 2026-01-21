<?php

namespace Database\Factories;

use App\Enums\Workout\Sport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Workout>
 */
class WorkoutFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'name' => fake()->randomElement([
                'Morning Run',
                'Intervals',
                'Tempo Run',
                'Long Run',
                'Recovery Run',
            ]),
            'sport' => Sport::Running,
            'notes' => fake()->boolean(50) ? fake()->sentence() : null,
            'scheduled_at' => fake()->dateTimeBetween('now', '+2 weeks'),
        ];
    }

    public function strength(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => fake()->randomElement(['Leg Day', 'Upper Body', 'Full Body', 'Core Workout']),
            'sport' => Sport::Strength,
        ]);
    }

    public function cardio(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => fake()->randomElement(['Cycling', 'Swimming', 'Rowing', 'Elliptical']),
            'sport' => Sport::Cardio,
        ]);
    }

    public function hiit(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => fake()->randomElement(['HIIT Circuit', 'Tabata', 'EMOM', 'AMRAP']),
            'sport' => Sport::Hiit,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'completed_at' => now(),
            'rpe' => fake()->numberBetween(1, 10),
            'feeling' => fake()->numberBetween(1, 5),
        ]);
    }
}
