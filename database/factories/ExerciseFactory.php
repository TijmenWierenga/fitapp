<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Exercise>
 */
class ExerciseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'force' => fake()->randomElement(['push', 'pull', 'static', null]),
            'level' => fake()->randomElement(['beginner', 'intermediate', 'expert']),
            'mechanic' => fake()->randomElement(['compound', 'isolation', null]),
            'equipment' => fake()->randomElement(['barbell', 'dumbbell', 'machine', 'body only', null]),
            'category' => fake()->randomElement(['strength', 'stretching', 'plyometrics', 'cardio']),
            'instructions' => [fake()->sentence(), fake()->sentence()],
        ];
    }
}
