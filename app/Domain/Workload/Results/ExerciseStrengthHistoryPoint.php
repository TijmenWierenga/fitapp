<?php

declare(strict_types=1);

namespace App\Domain\Workload\Results;

use DateTimeImmutable;

readonly class ExerciseStrengthHistoryPoint
{
    public function __construct(
        public DateTimeImmutable $date,
        public float $maxWeight,
        public float $volume,
        public float $estimated1RM,
    ) {}
}
