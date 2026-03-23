<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Fit;

use App\Models\Workout;

readonly class ImportResult
{
    /**
     * @param  list<string>  $matchedExercises
     * @param  list<string>  $unmatchedExercises
     * @param  list<string>  $warnings
     */
    public function __construct(
        public Workout $workout,
        public array $matchedExercises = [],
        public array $unmatchedExercises = [],
        public array $warnings = [],
    ) {}
}
