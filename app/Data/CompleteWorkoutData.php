<?php

declare(strict_types=1);

namespace App\Data;

readonly class CompleteWorkoutData
{
    /**
     * @param  array<int, InjuryEvaluationData>  $injuryEvaluations
     */
    public function __construct(
        public int $rpe,
        public int $feeling,
        public ?string $completionNotes = null,
        public array $injuryEvaluations = [],
    ) {}
}
