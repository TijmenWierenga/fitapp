<?php

namespace Database\Factories;

use App\Models\Block;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BlockExercise>
 */
class BlockExerciseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'block_id' => Block::factory(),
            'name' => fake()->randomElement([
                'Bench Press', 'Squat', 'Deadlift', 'Overhead Press',
                'Barbell Row', 'Pull-up', 'Lunge', 'Plank',
                'Push-up', 'Dumbbell Curl', 'Tricep Extension',
                'Running', 'Cycling', 'Rowing', 'Stretching',
            ]),
            'order' => 0,
        ];
    }
}
