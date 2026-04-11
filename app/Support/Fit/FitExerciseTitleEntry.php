<?php

declare(strict_types=1);

namespace App\Support\Fit;

readonly class FitExerciseTitleEntry
{
    public function __construct(
        public int $category,
        public int $name,
        public string $label,
    ) {}
}
