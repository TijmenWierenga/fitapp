<?php

declare(strict_types=1);

namespace App\Domain\Workload\Results;

readonly class StrengthProgressionResult
{
    public function __construct(
        public int $exerciseId,
        public string $exerciseName,
        public float $currentE1RM,
        public ?float $previousE1RM,
        public ?float $changePct,
    ) {}
}
