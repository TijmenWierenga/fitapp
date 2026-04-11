<?php

declare(strict_types=1);

namespace App\Support\Fit;

readonly class FitStepTarget
{
    public function __construct(
        public int $targetType,
        public ?int $targetValue,
        public ?int $customLow,
        public ?int $customHigh,
    ) {}
}
