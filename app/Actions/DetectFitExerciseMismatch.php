<?php

declare(strict_types=1);

namespace App\Actions;

use App\Actions\Garmin\ParsedActivityHelper;
use App\DataTransferObjects\Fit\ParsedActivity;
use App\Enums\Workout\BlockType;
use App\Models\Workout;

class DetectFitExerciseMismatch
{
    public function execute(Workout $workout, ParsedActivity $parsed): ?string
    {
        $activeSets = collect($parsed->sets)->filter->isActive();

        if ($activeSets->isEmpty()) {
            return null;
        }

        $groups = ParsedActivityHelper::groupSetsByExercise($activeSets);
        $fitExerciseCount = count($groups);

        $workout->loadMissing('sections.blocks.exercises');
        $plannedCount = $workout->sections
            ->flatMap(fn ($s) => $s->blocks)
            ->filter(fn ($b) => $b->block_type !== BlockType::DistanceDuration)
            ->flatMap(fn ($b) => $b->exercises)
            ->count();

        if ($fitExerciseCount !== $plannedCount) {
            return "FIT file has {$fitExerciseCount} exercises, planned workout has {$plannedCount}.";
        }

        return null;
    }
}
