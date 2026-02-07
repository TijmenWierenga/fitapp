<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Workout;

readonly class StrengthExerciseData
{
    public function __construct(
        public ?int $targetSets = null,
        public ?int $targetRepsMin = null,
        public ?int $targetRepsMax = null,
        public ?float $targetWeight = null,
        public ?float $targetRpe = null,
        public ?string $targetTempo = null,
        public ?int $restAfter = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            targetSets: $data['target_sets'] ?? null,
            targetRepsMin: $data['target_reps_min'] ?? null,
            targetRepsMax: $data['target_reps_max'] ?? null,
            targetWeight: $data['target_weight'] ?? null,
            targetRpe: $data['target_rpe'] ?? null,
            targetTempo: $data['target_tempo'] ?? null,
            restAfter: $data['rest_after'] ?? null,
        );
    }
}
