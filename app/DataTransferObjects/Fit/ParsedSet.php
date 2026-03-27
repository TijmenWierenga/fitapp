<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Fit;

readonly class ParsedSet
{
    public function __construct(
        public int $index,
        public int $setType,
        public ?int $duration,
        public ?int $repetitions,
        public ?float $weight,
        public ?int $exerciseCategory,
        public ?int $exerciseName,
    ) {}

    public function isActive(): bool
    {
        return $this->setType !== 0;
    }
}
