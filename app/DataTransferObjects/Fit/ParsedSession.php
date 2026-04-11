<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Fit;

use Carbon\CarbonImmutable;

readonly class ParsedSession
{
    public function __construct(
        public int $sport,
        public int $subSport,
        public CarbonImmutable $startTime,
        public ?int $totalElapsedTime,
        public ?float $totalDistance,
        public ?int $totalCalories,
        public ?int $avgHeartRate,
        public ?int $maxHeartRate,
        public ?int $avgPower,
        public ?string $workoutName,
    ) {}
}
