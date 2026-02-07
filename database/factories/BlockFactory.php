<?php

namespace Database\Factories;

use App\Enums\Workout\BlockType;
use App\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Block>
 */
class BlockFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'section_id' => Section::factory(),
            'block_type' => BlockType::StraightSets,
            'order' => 0,
        ];
    }

    public function circuit(): static
    {
        return $this->state(fn (array $attributes): array => [
            'block_type' => BlockType::Circuit,
            'rounds' => fake()->numberBetween(2, 5),
            'rest_between_exercises' => 30,
            'rest_between_rounds' => 60,
        ]);
    }

    public function superset(): static
    {
        return $this->state(fn (array $attributes): array => [
            'block_type' => BlockType::Superset,
            'rounds' => fake()->numberBetween(2, 4),
            'rest_between_rounds' => 90,
        ]);
    }

    public function interval(): static
    {
        return $this->state(fn (array $attributes): array => [
            'block_type' => BlockType::Interval,
            'rounds' => fake()->numberBetween(4, 10),
            'work_interval' => 30,
            'rest_interval' => 15,
        ]);
    }

    public function amrap(): static
    {
        return $this->state(fn (array $attributes): array => [
            'block_type' => BlockType::Amrap,
            'time_cap' => fake()->randomElement([600, 900, 1200]),
        ]);
    }

    public function forTime(): static
    {
        return $this->state(fn (array $attributes): array => [
            'block_type' => BlockType::ForTime,
            'time_cap' => fake()->randomElement([600, 900, 1200]),
        ]);
    }

    public function emom(): static
    {
        return $this->state(fn (array $attributes): array => [
            'block_type' => BlockType::Emom,
            'rounds' => fake()->numberBetween(8, 20),
            'work_interval' => 60,
        ]);
    }

    public function distanceDuration(): static
    {
        return $this->state(fn (array $attributes): array => [
            'block_type' => BlockType::DistanceDuration,
        ]);
    }

    public function rest(): static
    {
        return $this->state(fn (array $attributes): array => [
            'block_type' => BlockType::Rest,
        ]);
    }
}
