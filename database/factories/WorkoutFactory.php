<?php

namespace Database\Factories;

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
            'sport' => 'running',
            'scheduled_at' => fake()->dateTimeBetween('now', '+2 weeks'),
        ];
    }
}
