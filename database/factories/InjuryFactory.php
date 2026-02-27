<?php

namespace Database\Factories;

use App\Enums\BodyPart;
use App\Enums\InjuryType;
use App\Enums\Severity;
use App\Enums\Side;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Injury>
 */
class InjuryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'injury_type' => fake()->randomElement(InjuryType::cases()),
            'body_part' => fake()->randomElement(BodyPart::cases()),
            'severity' => fake()->randomElement(Severity::cases()),
            'side' => fake()->randomElement(Side::cases()),
            'started_at' => fake()->dateTimeBetween('-1 year', 'now'),
            'ended_at' => null,
            'notes' => fake()->boolean(50) ? fake()->sentence() : null,
            'how_it_happened' => fake()->boolean(30) ? fake()->sentence() : null,
            'current_symptoms' => fake()->boolean(30) ? fake()->sentence() : null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes): array => [
            'ended_at' => null,
        ]);
    }

    public function resolved(): static
    {
        return $this->state(function (array $attributes): array {
            $startedAt = $attributes['started_at'] ?? fake()->dateTimeBetween('-1 year', '-1 month');

            return [
                'started_at' => $startedAt,
                'ended_at' => fake()->dateTimeBetween($startedAt, 'now'),
            ];
        });
    }

    public function acute(): static
    {
        return $this->state(fn (array $attributes): array => [
            'injury_type' => InjuryType::Acute,
            'started_at' => fake()->dateTimeBetween('-2 weeks', 'now'),
        ]);
    }

    public function chronic(): static
    {
        return $this->state(fn (array $attributes): array => [
            'injury_type' => InjuryType::Chronic,
            'started_at' => fake()->dateTimeBetween('-2 years', '-6 months'),
        ]);
    }

    public function recurring(): static
    {
        return $this->state(fn (array $attributes): array => [
            'injury_type' => InjuryType::Recurring,
        ]);
    }

    public function postSurgery(): static
    {
        return $this->state(fn (array $attributes): array => [
            'injury_type' => InjuryType::PostSurgery,
        ]);
    }

    public function mild(): static
    {
        return $this->state(fn (array $attributes): array => [
            'severity' => Severity::Mild,
        ]);
    }

    public function moderate(): static
    {
        return $this->state(fn (array $attributes): array => [
            'severity' => Severity::Moderate,
        ]);
    }

    public function severe(): static
    {
        return $this->state(fn (array $attributes): array => [
            'severity' => Severity::Severe,
        ]);
    }
}
