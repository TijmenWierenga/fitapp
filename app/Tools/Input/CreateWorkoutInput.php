<?php

declare(strict_types=1);

namespace App\Tools\Input;

readonly class CreateWorkoutInput
{
    /**
     * @param  array<int, array<string, mixed>>  $sections
     */
    public function __construct(
        public string $name,
        public string $activity,
        public string $scheduledAt,
        public ?string $notes,
        public array $sections,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            activity: $data['activity'],
            scheduledAt: $data['scheduled_at'],
            notes: $data['notes'] ?? null,
            sections: $data['sections'],
        );
    }
}
