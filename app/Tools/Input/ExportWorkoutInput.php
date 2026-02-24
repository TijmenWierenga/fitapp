<?php

declare(strict_types=1);

namespace App\Tools\Input;

readonly class ExportWorkoutInput
{
    public function __construct(
        public int $workoutId,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            workoutId: $data['workout_id'],
        );
    }
}
