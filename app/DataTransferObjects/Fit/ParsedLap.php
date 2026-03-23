<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Fit;

use Carbon\CarbonImmutable;

readonly class ParsedLap
{
    public function __construct(
        public int $index,
        public CarbonImmutable $startTime,
        public int $totalElapsedTime,
        public ?float $totalDistance,
        public ?int $avgHeartRate,
        public ?int $maxHeartRate,
        public ?int $avgSpeed,
        public ?int $avgPower,
        public ?int $maxPower,
        public ?int $avgCadence,
        public ?int $totalAscent,
    ) {}
}
