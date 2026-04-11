<?php

declare(strict_types=1);

namespace App\Support\Fit\BlockMapper;

use App\Models\Block;
use App\Models\DurationExercise;

class RestMapper implements BlockFitMapper
{
    public function map(Block $block, int $sectionIntensity, FitStepBuilder $builder): void
    {
        $exercise = $block->exercises->first();
        $duration = 60;

        if ($exercise?->exerciseable instanceof DurationExercise && $exercise->exerciseable->target_duration) {
            $duration = $exercise->exerciseable->target_duration;
        }

        $builder->addTimeStep($exercise?->name ?? 'Rest', $duration, 1);
    }
}
