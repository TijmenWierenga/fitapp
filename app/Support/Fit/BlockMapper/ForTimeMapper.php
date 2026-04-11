<?php

declare(strict_types=1);

namespace App\Support\Fit\BlockMapper;

use App\Models\Block;

class ForTimeMapper implements BlockFitMapper
{
    public function map(Block $block, int $sectionIntensity, FitStepBuilder $builder): void
    {
        $startIndex = $builder->getStepIndex();

        foreach ($block->exercises as $exercise) {
            $builder->addExerciseStep($exercise, $sectionIntensity);
        }

        if ($block->rounds && $block->rounds > 1) {
            $builder->addRepeatStep($startIndex, $block->rounds);
        }
    }
}
