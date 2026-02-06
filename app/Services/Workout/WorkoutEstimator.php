<?php

declare(strict_types=1);

namespace App\Services\Workout;

use App\Enums\Workout\BlockType;
use App\Enums\Workout\IntervalIntensity;
use App\Models\ExerciseGroup;
use App\Models\IntervalBlock;
use App\Models\RestBlock;
use App\Models\Workout;
use App\Models\WorkoutBlock;

class WorkoutEstimator
{
    /**
     * Default pace per intensity (seconds per km) for estimating interval durations.
     *
     * @var array<string, int>
     */
    private const DEFAULT_PACE_BY_INTENSITY = [
        'easy' => 360,
        'moderate' => 330,
        'threshold' => 270,
        'tempo' => 300,
        'vo2max' => 240,
        'sprint' => 210,
    ];

    /**
     * Estimate total workout duration in seconds.
     */
    public function estimateDuration(Workout $workout): int
    {
        $workout->load([
            'blockTree.blockable',
            'blockTree.nestedChildren.blockable',
            'blockTree.nestedChildren.nestedChildren.blockable',
        ]);

        $totalDuration = 0;

        foreach ($workout->blockTree as $block) {
            $totalDuration += $this->calculateBlockDuration($block);
        }

        return (int) $totalDuration;
    }

    /**
     * Estimate total workout distance in meters.
     */
    public function estimateDistance(Workout $workout): int
    {
        $workout->load([
            'blockTree.blockable',
            'blockTree.nestedChildren.blockable',
            'blockTree.nestedChildren.nestedChildren.blockable',
        ]);

        $totalDistance = 0;

        foreach ($workout->blockTree as $block) {
            $totalDistance += $this->calculateBlockDistance($block);
        }

        return (int) $totalDistance;
    }

    /**
     * Calculate duration for a single block including its children.
     */
    protected function calculateBlockDuration(WorkoutBlock $block): int
    {
        $singleIterationDuration = match ($block->type) {
            BlockType::Interval => $this->calculateIntervalDuration($block),
            BlockType::ExerciseGroup => $this->calculateExerciseGroupDuration($block),
            BlockType::Rest => $this->calculateRestDuration($block),
            BlockType::Group => $this->calculateGroupDuration($block),
            default => 0,
        };

        $totalDuration = $singleIterationDuration * $block->repeat_count;

        if ($block->repeat_count > 1 && $block->rest_between_repeats_seconds !== null) {
            $totalDuration += $block->rest_between_repeats_seconds * ($block->repeat_count - 1);
        }

        return (int) $totalDuration;
    }

    /**
     * Calculate distance for a single block including its children.
     */
    protected function calculateBlockDistance(WorkoutBlock $block): int
    {
        $singleIterationDistance = match ($block->type) {
            BlockType::Interval => $this->calculateIntervalDistance($block),
            BlockType::Group => $this->calculateGroupDistance($block),
            default => 0,
        };

        return (int) ($singleIterationDistance * $block->repeat_count);
    }

    /**
     * Calculate duration for an interval block.
     */
    protected function calculateIntervalDuration(WorkoutBlock $block): int
    {
        $intervalBlock = $block->blockable;

        if (! $intervalBlock instanceof IntervalBlock) {
            return 0;
        }

        if ($intervalBlock->duration_seconds !== null) {
            return $intervalBlock->duration_seconds;
        }

        if ($intervalBlock->distance_meters !== null) {
            $pace = $intervalBlock->target_pace_seconds_per_km
                ?? $this->getDefaultPaceForIntensity($intervalBlock->intensity);

            return (int) round(($intervalBlock->distance_meters / 1000) * $pace);
        }

        return 0;
    }

    /**
     * Calculate distance for an interval block.
     */
    protected function calculateIntervalDistance(WorkoutBlock $block): int
    {
        $intervalBlock = $block->blockable;

        if (! $intervalBlock instanceof IntervalBlock) {
            return 0;
        }

        return $intervalBlock->distance_meters ?? 0;
    }

    /**
     * Calculate duration for an exercise group block.
     */
    protected function calculateExerciseGroupDuration(WorkoutBlock $block): int
    {
        $exerciseGroup = $block->blockable;

        if (! $exerciseGroup instanceof ExerciseGroup) {
            return 0;
        }

        $exerciseGroup->load('entries');

        $totalDuration = 0;

        foreach ($exerciseGroup->entries as $entry) {
            if ($entry->reps !== null) {
                $entryDuration = $entry->sets * $entry->reps * 3;
            } elseif ($entry->duration_seconds !== null) {
                $entryDuration = $entry->sets * $entry->duration_seconds;
            } else {
                $entryDuration = 0;
            }

            if ($entry->rest_between_sets_seconds !== null) {
                $entryDuration += $entry->rest_between_sets_seconds * ($entry->sets - 1);
            }

            $totalDuration += $entryDuration;
        }

        $totalDuration *= $exerciseGroup->rounds;

        if ($exerciseGroup->rounds > 1 && $exerciseGroup->rest_between_rounds_seconds !== null) {
            $totalDuration += $exerciseGroup->rest_between_rounds_seconds * ($exerciseGroup->rounds - 1);
        }

        return (int) $totalDuration;
    }

    /**
     * Calculate duration for a rest block.
     */
    protected function calculateRestDuration(WorkoutBlock $block): int
    {
        $restBlock = $block->blockable;

        if (! $restBlock instanceof RestBlock) {
            return 0;
        }

        return $restBlock->duration_seconds ?? 0;
    }

    /**
     * Calculate duration for a group block (sum of children).
     */
    protected function calculateGroupDuration(WorkoutBlock $block): int
    {
        $totalDuration = 0;

        foreach ($block->children as $child) {
            $totalDuration += $this->calculateBlockDuration($child);
        }

        return (int) $totalDuration;
    }

    /**
     * Calculate distance for a group block (sum of children).
     */
    protected function calculateGroupDistance(WorkoutBlock $block): int
    {
        $totalDistance = 0;

        foreach ($block->children as $child) {
            $totalDistance += $this->calculateBlockDistance($child);
        }

        return (int) $totalDistance;
    }

    /**
     * Get default pace for an intensity level.
     */
    protected function getDefaultPaceForIntensity(?IntervalIntensity $intensity): int
    {
        if ($intensity === null) {
            return self::DEFAULT_PACE_BY_INTENSITY['moderate'];
        }

        return self::DEFAULT_PACE_BY_INTENSITY[$intensity->value] ?? self::DEFAULT_PACE_BY_INTENSITY['moderate'];
    }
}
