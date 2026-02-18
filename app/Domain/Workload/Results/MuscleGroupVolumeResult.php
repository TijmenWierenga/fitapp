<?php

declare(strict_types=1);

namespace App\Domain\Workload\Results;

use App\Domain\Workload\Enums\Trend;

readonly class MuscleGroupVolumeResult
{
    public function __construct(
        public int $muscleGroupId,
        public string $name,
        public string $label,
        public string $bodyPart,
        public float $currentWeekSets,
        public float $fourWeekAverageSets,
        public Trend $trend,
    ) {}
}
