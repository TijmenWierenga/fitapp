<?php

namespace Database\Factories;

use App\Enums\Workout\BlockType;
use App\Models\Workout;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WorkoutBlock>
 */
class WorkoutBlockFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workout_id' => Workout::factory(),
            'parent_id' => null,
            'type' => BlockType::Group,
            'position' => 0,
            'label' => null,
            'repeat_count' => 1,
            'rest_between_repeats_seconds' => null,
            'blockable_type' => null,
            'blockable_id' => null,
        ];
    }

    public function group(?string $label = null): static
    {
        return $this->state(fn (): array => [
            'type' => BlockType::Group,
            'label' => $label,
        ]);
    }

    public function interval(): static
    {
        return $this->state(fn (): array => [
            'type' => BlockType::Interval,
        ]);
    }

    public function exerciseGroup(): static
    {
        return $this->state(fn (): array => [
            'type' => BlockType::ExerciseGroup,
        ]);
    }

    public function rest(): static
    {
        return $this->state(fn (): array => [
            'type' => BlockType::Rest,
        ]);
    }

    public function note(): static
    {
        return $this->state(fn (): array => [
            'type' => BlockType::Note,
        ]);
    }
}
