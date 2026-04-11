<?php

declare(strict_types=1);

namespace App\Support\Fit\BlockMapper;

use App\Models\Block;

class CircuitMapper implements BlockFitMapper
{
    public function map(Block $block, int $sectionIntensity, FitStepBuilder $builder): void
    {
        $startIndex = $builder->getStepIndex();
        $exercises = $block->exercises;

        foreach ($exercises as $index => $exercise) {
            $builder->addExerciseStep($exercise, $sectionIntensity);

            $isLast = $index === $exercises->count() - 1;

            if (! $isLast && $block->rest_between_exercises && $block->rest_between_exercises > 0) {
                $builder->addRestStep('Rest', $block->rest_between_exercises);
            }
        }

        if ($block->rest_between_rounds && $block->rest_between_rounds > 0) {
            $builder->addRestStep('Rest', $block->rest_between_rounds);
        }

        if ($block->rounds && $block->rounds > 1) {
            $builder->addRepeatStep($startIndex, $block->rounds);
        }
    }
}
