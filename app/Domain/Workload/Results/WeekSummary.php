<?php

declare(strict_types=1);

namespace App\Domain\Workload\Results;

readonly class WeekSummary
{
    public function __construct(
        public int $weekOffset,
        public int $totalLoad,
        public int $sessionCount,
    ) {}
}
