<?php

declare(strict_types=1);

namespace App\Domain\Workload\Results;

use App\Domain\Workload\Enums\AcwrZone;

readonly class EwmaLoadResult
{
    /**
     * @param  array<DailyEwmaPoint>  $dailyPoints
     */
    public function __construct(
        public float $currentAcuteLoad,
        public float $currentChronicLoad,
        public ?float $acwr,
        public float $tsb,
        public AcwrZone $acwrZone,
        public int $totalSessions,
        public array $dailyPoints,
    ) {}
}
