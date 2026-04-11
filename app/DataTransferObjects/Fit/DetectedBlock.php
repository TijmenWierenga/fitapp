<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Fit;

use App\Enums\Fit\DetectedBlockType;

readonly class DetectedBlock
{
    /**
     * @param  array<string, ExerciseGroup>  $exercises
     */
    public function __construct(
        public DetectedBlockType $type,
        public array $exercises,
    ) {}
}
