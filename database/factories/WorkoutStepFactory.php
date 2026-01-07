<?php

namespace Database\Factories;

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
            'parent_step_id' => null,
            'sort_order' => 0,
            'step_kind' => 'run',
            'intensity' => 'active',
            'name' => null,
            'notes' => null,
            'duration_type' => 'distance',
            'duration_value' => 1000,
            'target_type' => 'none',
            'target_mode' => null,
            'target_zone' => null,
            'target_low' => null,
            'target_high' => null,
            'repeat_count' => null,
            'skip_last_recovery' => false,
        ];
    }

    /**
     * Create a warmup step
     */
    public function warmup(): static
    {
        return $this->state(fn (array $attributes) => [
            'step_kind' => 'warmup',
            'intensity' => 'warmup',
            'duration_type' => 'time',
            'duration_value' => 300,
        ]);
    }

    /**
     * Create a cooldown step
     */
    public function cooldown(): static
    {
        return $this->state(fn (array $attributes) => [
            'step_kind' => 'cooldown',
            'intensity' => 'cooldown',
            'duration_type' => 'time',
            'duration_value' => 300,
        ]);
    }

    /**
     * Create a recovery step
     */
    public function recovery(): static
    {
        return $this->state(fn (array $attributes) => [
            'step_kind' => 'recovery',
            'intensity' => 'rest',
            'duration_type' => 'time',
            'duration_value' => 60,
        ]);
    }

    /**
     * Create a repeat block
     */
    public function repeat(int $count = 2): static
    {
        return $this->state(fn (array $attributes) => [
            'step_kind' => 'repeat',
            'intensity' => 'active',
            'duration_type' => null,
            'duration_value' => null,
            'target_type' => null,
            'repeat_count' => $count,
        ]);
    }

    /**
     * Set time duration
     */
    public function withTime(int $seconds): static
    {
        return $this->state(fn (array $attributes) => [
            'duration_type' => 'time',
            'duration_value' => $seconds,
        ]);
    }

    /**
     * Set distance duration
     */
    public function withDistance(int $meters): static
    {
        return $this->state(fn (array $attributes) => [
            'duration_type' => 'distance',
            'duration_value' => $meters,
        ]);
    }

    /**
     * Set HR zone target
     */
    public function withHRZone(int $zone): static
    {
        return $this->state(fn (array $attributes) => [
            'target_type' => 'heart_rate',
            'target_mode' => 'zone',
            'target_zone' => $zone,
        ]);
    }

    /**
     * Set HR range target
     */
    public function withHRRange(int $low, int $high): static
    {
        return $this->state(fn (array $attributes) => [
            'target_type' => 'heart_rate',
            'target_mode' => 'range',
            'target_low' => $low,
            'target_high' => $high,
        ]);
    }

    /**
     * Set pace zone target
     */
    public function withPaceZone(int $zone): static
    {
        return $this->state(fn (array $attributes) => [
            'target_type' => 'pace',
            'target_mode' => 'zone',
            'target_zone' => $zone,
        ]);
    }

    /**
     * Set pace range target
     */
    public function withPaceRange(int $low, int $high): static
    {
        return $this->state(fn (array $attributes) => [
            'target_type' => 'pace',
            'target_mode' => 'range',
            'target_low' => $low,
            'target_high' => $high,
        ]);
    }
}
