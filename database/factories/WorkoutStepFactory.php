<?php

namespace Database\Factories;

use App\Enums\DurationType;
use App\Enums\Intensity;
use App\Enums\StepType;
use App\Enums\TargetType;
use App\Models\Workout;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WorkoutStep>
 */
class WorkoutStepFactory extends Factory
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
            'parent_id' => null,
            'order' => 0,
            'type' => StepType::Step,
            'intensity' => fake()->randomElement(Intensity::cases()),
            'duration_type' => DurationType::Time,
            'duration_value' => '600', // 10 minutes
            'target_type' => TargetType::Open,
        ];
    }
}
