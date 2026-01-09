<?php

namespace Database\Factories;

use App\Enums\Workout\DurationType;
use App\Enums\Workout\Intensity;
use App\Enums\Workout\StepKind;
use App\Enums\Workout\TargetType;
use App\Models\Workout;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Step>
 */
class StepFactory extends Factory
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
            'parent_step_id' => null,
            'sort_order' => 0,
            'step_kind' => StepKind::Run,
            'intensity' => Intensity::Active,
            'duration_type' => DurationType::Distance,
            'duration_value' => 1000,
            'target_type' => TargetType::None,
        ];
    }
}
