<?php

namespace Database\Factories;

use App\Enums\Workout\IntervalIntensity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\IntervalBlock>
 */
class IntervalBlockFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'duration_seconds' => 300,
            'distance_meters' => null,
            'target_pace_seconds_per_km' => null,
            'target_heart_rate_zone' => null,
            'intensity' => IntervalIntensity::Moderate,
        ];
    }

    public function distanceBased(int $meters = 1000): static
    {
        return $this->state(fn (): array => [
            'duration_seconds' => null,
            'distance_meters' => $meters,
        ]);
    }

    public function easy(): static
    {
        return $this->state(fn (): array => [
            'intensity' => IntervalIntensity::Easy,
        ]);
    }

    public function threshold(): static
    {
        return $this->state(fn (): array => [
            'intensity' => IntervalIntensity::Threshold,
        ]);
    }
}
