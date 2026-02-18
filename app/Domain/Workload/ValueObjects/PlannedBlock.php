<?php

declare(strict_types=1);

namespace App\Domain\Workload\ValueObjects;

use App\Enums\Workout\BlockType;

readonly class PlannedBlock
{
    /**
     * @param  array<int|null>  $exerciseDurations
     */
    public function __construct(
        public BlockType $blockType,
        public ?int $rounds,
        public ?int $restBetweenExercises,
        public ?int $restBetweenRounds,
        public ?int $timeCap,
        public ?int $workInterval,
        public ?int $restInterval,
        public array $exerciseDurations,
    ) {}
}
