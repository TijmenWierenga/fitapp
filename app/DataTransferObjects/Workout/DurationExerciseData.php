<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Workout;

readonly class DurationExerciseData
{
    public function __construct(
        public ?int $targetDuration = null,
        public ?float $targetRpe = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            targetDuration: $data['target_duration'] ?? null,
            targetRpe: $data['target_rpe'] ?? null,
        );
    }
}
