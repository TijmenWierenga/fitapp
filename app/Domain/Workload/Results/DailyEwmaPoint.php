<?php

declare(strict_types=1);

namespace App\Domain\Workload\Results;

readonly class DailyEwmaPoint
{
    public function __construct(
        public string $date,
        public float $acuteLoad,
        public float $chronicLoad,
        public ?float $acwr,
        public float $tsb,
    ) {}
}
