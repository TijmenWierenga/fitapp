<?php

declare(strict_types=1);

namespace App\Support\Fit\BlockMapper;

use App\Models\Block;
use App\Models\CardioExercise;
use App\Models\DurationExercise;

class DistanceDurationMapper implements BlockFitMapper
{
    public function map(Block $block, int $sectionIntensity, FitStepBuilder $builder): void
    {
        foreach ($block->exercises as $exercise) {
            $exerciseable = $exercise->exerciseable;
            $garminCategory = $exercise->exercise?->garmin_exercise_category?->value;
            $garminName = $exercise->exercise?->garmin_exercise_name;

            if ($exerciseable instanceof CardioExercise) {
                $builder->addCardioExerciseStep($exercise->name, $exerciseable, $sectionIntensity, $garminCategory, $garminName);
            } elseif ($exerciseable instanceof DurationExercise && $exerciseable->target_duration) {
                $builder->addTimeStep($exercise->name, $exerciseable->target_duration, $sectionIntensity, $garminCategory, $garminName);
            } else {
                $builder->addOpenStep($exercise->name, $sectionIntensity, exerciseCategory: $garminCategory, exerciseName: $garminName);
            }
        }
    }
}
