<?php

namespace Database\Factories;

use App\Enums\Fit\GarminExerciseCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

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
        $name = fake()->unique()->words(3, true);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'force' => fake()->randomElement(['push', 'pull', 'static', null]),
            'level' => fake()->randomElement(['beginner', 'intermediate', 'expert']),
            'mechanic' => fake()->randomElement(['compound', 'isolation', null]),
            'equipment' => fake()->randomElement(['barbell', 'dumbbell', 'machine', 'body only', null]),
            'category' => fake()->randomElement(['strength', 'stretching', 'plyometrics', 'cardio']),
            'instructions' => [fake()->sentence(), fake()->sentence()],
            'garmin_exercise_category' => null,
            'garmin_exercise_name' => null,
        ];
    }

    public function withGarminMapping(GarminExerciseCategory $category = GarminExerciseCategory::BenchPress, int $exerciseName = 1): static
    {
        return $this->state(fn (array $attributes): array => [
            'garmin_exercise_category' => $category,
            'garmin_exercise_name' => $exerciseName,
        ]);
    }
}
