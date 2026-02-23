<?php

declare(strict_types=1);

namespace App\Tools\Input;

readonly class UpdateFitnessProfileInput
{
    /**
     * @param  array<string>  $providedFields
     */
    public function __construct(
        public string $primaryGoal,
        public ?string $goalDetails,
        public int $availableDaysPerWeek,
        public int $minutesPerSession,
        public ?bool $preferGarminExercises,
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
            providedFields: array_keys($data),
        );
    }
}
