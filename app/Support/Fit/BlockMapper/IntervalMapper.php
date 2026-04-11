<?php

declare(strict_types=1);

namespace App\Support\Fit\BlockMapper;

use App\Models\Block;

class IntervalMapper implements BlockFitMapper
{
    public function map(Block $block, int $sectionIntensity, FitStepBuilder $builder): void
    {
        $startIndex = $builder->getStepIndex();

        if ($block->work_interval && $block->work_interval > 0) {
            $firstExercise = $block->exercises->first();
            $name = $firstExercise?->name ?? 'Work';
            $garminCategory = $firstExercise?->exercise?->garmin_exercise_category?->value;
            $garminName = $firstExercise?->exercise?->garmin_exercise_name;
            $notes = $builder->intervalWorkNotes($block->exercises->all(), $block->notes);

            $builder->addTimeStep($name, $block->work_interval, $sectionIntensity, $garminCategory, $garminName, $notes);
        } else {
            foreach ($block->exercises as $exercise) {
                $builder->addExerciseStep($exercise, $sectionIntensity);
            }
        }

        if ($block->rest_interval && $block->rest_interval > 0) {
            $builder->addRestStep('Rest', $block->rest_interval, $block->notes);
        }

        if ($block->rounds && $block->rounds > 1) {
            $builder->addRepeatStep($startIndex, $block->rounds);
        }
    }
}
