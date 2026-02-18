<?php

declare(strict_types=1);

namespace App\Domain\Workload\ValueObjects;

readonly class MuscleGroupMapping
{
    public function __construct(
        public int $muscleGroupId,
        public string $name,
        public string $label,
        public string $bodyPart,
        public float $loadFactor,
    ) {}
}
