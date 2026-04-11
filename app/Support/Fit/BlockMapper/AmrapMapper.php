<?php

declare(strict_types=1);

namespace App\Support\Fit\BlockMapper;

use App\Models\Block;

class AmrapMapper implements BlockFitMapper
{
    public function map(Block $block, int $sectionIntensity, FitStepBuilder $builder): void
    {
        $startIndex = $builder->getStepIndex();

        foreach ($block->exercises as $exercise) {
            $builder->addExerciseStep($exercise, $sectionIntensity);
        }

        if ($block->time_cap && $block->time_cap > 0) {
            $builder->addRepeatUntilTimeStep($startIndex, $block->time_cap);
        }
    }
}
