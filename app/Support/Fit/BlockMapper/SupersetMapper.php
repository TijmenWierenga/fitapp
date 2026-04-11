<?php

declare(strict_types=1);

namespace App\Support\Fit\BlockMapper;

use App\Models\Block;

class SupersetMapper implements BlockFitMapper
{
    public function map(Block $block, int $sectionIntensity, FitStepBuilder $builder): void
    {
        $startIndex = $builder->getStepIndex();

        foreach ($block->exercises as $exercise) {
            $builder->addExerciseStep($exercise, $sectionIntensity);
        }

        if ($block->rest_between_rounds && $block->rest_between_rounds > 0) {
            $builder->addRestStep('Rest', $block->rest_between_rounds);
        }

        if ($block->rounds && $block->rounds > 1) {
            $builder->addRepeatStep($startIndex, $block->rounds);
        }
    }
}
