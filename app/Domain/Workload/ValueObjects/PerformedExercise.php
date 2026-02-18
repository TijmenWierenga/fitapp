<?php

declare(strict_types=1);

namespace App\Domain\Workload\ValueObjects;

use DateTimeImmutable;

readonly class PerformedExercise
{
    /**
     * @param  array<MuscleGroupMapping>  $muscleGroups
     */
    public function __construct(
        public DateTimeImmutable $completedAt,
        public int $sets,
        public string $exerciseType,
        public array $muscleGroups,
    ) {}
}
