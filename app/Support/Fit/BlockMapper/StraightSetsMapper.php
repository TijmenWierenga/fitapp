<?php

declare(strict_types=1);

namespace App\Support\Fit\BlockMapper;

use App\Models\Block;
use App\Models\StrengthExercise;

class StraightSetsMapper implements BlockFitMapper
{
    public function map(Block $block, int $sectionIntensity, FitStepBuilder $builder): void
    {
        foreach ($block->exercises as $exercise) {
            $startIndex = $builder->getStepIndex();

            $builder->addExerciseStep($exercise, $sectionIntensity);

            $sets = null;
            $restAfter = null;

            if ($exercise->exerciseable instanceof StrengthExercise) {
                $sets = $exercise->exerciseable->target_sets;
                $restAfter = $exercise->exerciseable->rest_after;
            }

            if ($restAfter && $restAfter > 0) {
                $builder->addRestStep('Rest', $restAfter);
            }

            if ($sets && $sets > 1) {
                $builder->addRepeatStep($startIndex, $sets);
            }
        }
    }
}
