<?php

declare(strict_types=1);

namespace App\Domain\Workload\ValueObjects;

use DateTimeImmutable;

readonly class StrengthRecord
{
    public function __construct(
        public int $exerciseId,
        public string $exerciseName,
        public DateTimeImmutable $performedAt,
        public float $weight,
        public int $reps,
    ) {}

    public function estimated1RM(): float
    {
        if ($this->weight === 0.0) {
            return 0.0;
        }

        if ($this->reps <= 1) {
            return $this->weight;
        }

        return $this->weight * (1 + $this->reps / 30);
    }
}
