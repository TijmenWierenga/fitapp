<?php

declare(strict_types=1);

namespace App\Tools\Input;

readonly class UpdateFitnessProfileInput
{
    /**
     * @param  array<string>|null  $homeEquipment
     * @param  array<string>  $providedFields
     */
    public function __construct(
        public string $primaryGoal,
        public ?string $goalDetails,
        public int $availableDaysPerWeek,
        public int $minutesPerSession,
        public ?bool $preferGarminExercises,
        public ?string $experienceLevel,
        public ?string $dateOfBirth,
        public ?string $biologicalSex,
        public ?float $bodyWeightKg,
        public ?int $heightCm,
        public ?bool $hasGymAccess,
        public ?array $homeEquipment,
        private array $providedFields,
    ) {}

    public function has(string $field): bool
    {
        return in_array($field, $this->providedFields, true);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            primaryGoal: $data['primary_goal'],
            goalDetails: $data['goal_details'] ?? null,
            availableDaysPerWeek: $data['available_days_per_week'],
            minutesPerSession: $data['minutes_per_session'],
            preferGarminExercises: $data['prefer_garmin_exercises'] ?? null,
            experienceLevel: $data['experience_level'] ?? null,
            dateOfBirth: $data['date_of_birth'] ?? null,
            biologicalSex: $data['biological_sex'] ?? null,
            bodyWeightKg: isset($data['body_weight_kg']) ? (float) $data['body_weight_kg'] : null,
            heightCm: isset($data['height_cm']) ? (int) $data['height_cm'] : null,
            hasGymAccess: $data['has_gym_access'] ?? null,
            homeEquipment: $data['home_equipment'] ?? null,
            providedFields: array_keys($data),
        );
    }
}
