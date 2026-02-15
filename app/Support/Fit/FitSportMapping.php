<?php

declare(strict_types=1);

namespace App\Support\Fit;

readonly class FitSportMapping
{
    public function __construct(
        public int $sport,
        public int $subSport,
    ) {}
}
