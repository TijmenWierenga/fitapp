<?php

namespace Database\Factories;

use App\Enums\InjuryReportType;
use App\Models\Injury;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InjuryReport>
 */
class InjuryReportFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'injury_id' => Injury::factory(),
            'user_id' => User::factory(),
            'type' => fake()->randomElement(InjuryReportType::cases()),
            'content' => fake()->paragraph(),
            'reported_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ];
    }

    public function selfReporting(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => InjuryReportType::SelfReporting,
        ]);
    }

    public function ptVisit(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => InjuryReportType::PtVisit,
        ]);
    }

    public function milestone(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => InjuryReportType::Milestone,
        ]);
    }
}
