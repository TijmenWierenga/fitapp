<?php

declare(strict_types=1);

namespace App\Tools\Input;

readonly class CompleteWorkoutInput
{
    public function __construct(
        public int $workoutId,
        public int $rpe,
        public int $feeling,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            workoutId: $data['workout_id'],
            rpe: $data['rpe'],
            feeling: $data['feeling'],
        );
    }
}
