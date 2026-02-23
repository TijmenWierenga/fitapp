<?php

declare(strict_types=1);

namespace App\Tools\Input;

readonly class UpdateWorkoutInput
{
    /**
     * @param  array<int, array<string, mixed>>|null  $sections
     * @param  array<string>  $providedFields
     */
    public function __construct(
        public int $workoutId,
        public ?string $name,
        public ?string $activity,
        public ?string $scheduledAt,
        public ?string $notes,
        public ?array $sections,
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
            workoutId: $data['workout_id'],
            name: $data['name'] ?? null,
            activity: $data['activity'] ?? null,
            scheduledAt: $data['scheduled_at'] ?? null,
            notes: $data['notes'] ?? null,
            sections: $data['sections'] ?? null,
            providedFields: array_keys($data),
        );
    }
}
