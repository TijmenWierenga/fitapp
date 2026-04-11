<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Fit;

use App\DataTransferObjects\Workout\SectionData;
use Illuminate\Support\Collection;

readonly class BuildWorkoutResult
{
    /**
     * @param  Collection<int, SectionData>  $sections
     * @param  list<string>  $matched
     * @param  list<string>  $unmatched
     * @param  list<string>  $warnings
     */
    public function __construct(
        public Collection $sections,
        public array $matched,
        public array $unmatched,
        public array $warnings,
    ) {}
}
