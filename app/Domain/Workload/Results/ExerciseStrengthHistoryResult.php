<?php

declare(strict_types=1);

namespace App\Domain\Workload\Results;

readonly class ExerciseStrengthHistoryResult
{
    /**
     * @param  array<ExerciseStrengthHistoryPoint>  $points
     */
    public function __construct(
        public int $exerciseId,
        public string $exerciseName,
        public array $points,
    ) {}
}
