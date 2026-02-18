<?php

declare(strict_types=1);

namespace App\Domain\Workload\Results;

readonly class SessionLoadResult
{
    /**
     * @param  array<WeekSummary>  $previousWeeks
     */
    public function __construct(
        public int $currentWeeklyTotal,
        public int $currentSessionCount,
        public float $monotony,
        public float $strain,
        public array $previousWeeks,
        public float $weekOverWeekChangePct,
        public bool $weekOverWeekWarning,
        public bool $monotonyWarning,
    ) {}
}
