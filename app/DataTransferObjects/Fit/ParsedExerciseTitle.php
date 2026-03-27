<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Fit;

readonly class ParsedExerciseTitle
{
    public function __construct(
        public int $exerciseCategory,
        public int $exerciseName,
        public string $displayName,
    ) {}
}
