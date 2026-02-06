<?php

declare(strict_types=1);

namespace App\Services\MuscleLoad;

use App\Enums\Workout\BlockType;
use App\Enums\Workout\IntervalIntensity;
use App\Models\ActivityMuscleLoad;
use App\Models\ExerciseGroup;
use App\Models\IntervalBlock;
use App\Models\Workout;
use App\Models\WorkoutBlock;
use App\Support\Workout\MuscleLoadSummary;

class MuscleLoadCalculator
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
     * Calculate muscle load for a workout.
     */
    public function calculate(Workout $workout): MuscleLoadSummary
    {
        $workout->load([
            'blockTree.blockable',
            'blockTree.nestedChildren.blockable',
            'blockTree.nestedChildren.nestedChildren.blockable',
        ]);

        $loads = [];

        foreach ($workout->blockTree as $block) {
            $this->processBlock($block, $workout, $loads, 1.0);
        }

        return new MuscleLoadSummary($loads);
    }

    /**
     * Recursively process a block and its children.
     *
     * @param  array<string, array{total: float, sources: array<int, array{description: string, load: float}>}>  $loads
     */
    protected function processBlock(
        WorkoutBlock $block,
        Workout $workout,
        array &$loads,
        float $repeatMultiplier
    ): void {
        $currentMultiplier = $repeatMultiplier * $block->repeat_count;

        match ($block->type) {
            BlockType::Interval => $this->processIntervalBlock($block, $workout, $loads, $currentMultiplier),
            BlockType::ExerciseGroup => $this->processExerciseGroupBlock($block, $loads, $currentMultiplier),
            BlockType::Group => $this->processGroupBlock($block, $workout, $loads, $currentMultiplier),
            default => null,
        };
    }

    /**
     * @param  array<string, array{total: float, sources: array<int, array{description: string, load: float}>}>  $loads
     */
    protected function processIntervalBlock(
        WorkoutBlock $block,
        Workout $workout,
        array &$loads,
        float $repeatMultiplier
    ): void {
        $intervalBlock = $block->blockable;

        if (! $intervalBlock instanceof IntervalBlock) {
            return;
        }

        if ($intervalBlock->intensity === null) {
            return;
        }

        $durationMinutes = $this->calculateIntervalDuration($intervalBlock);

        if ($durationMinutes === 0.0) {
            return;
        }

        $activityLoads = ActivityMuscleLoad::query()
            ->where('activity', $workout->activity)
            ->get();

        $intensityMultiplier = $intervalBlock->intensity->intensityMultiplier();

        foreach ($activityLoads as $activityLoad) {
            $load = $durationMinutes * $intensityMultiplier * $activityLoad->load_factor * $repeatMultiplier;

            if ($load <= 0) {
                continue;
            }

            $muscleKey = $activityLoad->muscle_group->value;

            if (! isset($loads[$muscleKey])) {
                $loads[$muscleKey] = ['total' => 0.0, 'sources' => []];
            }

            $loads[$muscleKey]['total'] += $load;
            $loads[$muscleKey]['sources'][] = [
                'description' => $this->formatIntervalDescription($block, $intervalBlock, $workout->activity->value),
                'load' => $load,
            ];
        }
    }

    /**
     * Calculate duration in minutes for an interval block.
     */
    protected function calculateIntervalDuration(IntervalBlock $intervalBlock): float
    {
        if ($intervalBlock->duration_seconds !== null) {
            return $intervalBlock->duration_seconds / 60;
        }

        if ($intervalBlock->distance_meters !== null) {
            $pace = $intervalBlock->target_pace_seconds_per_km
                ?? $this->getDefaultPaceForIntensity($intervalBlock->intensity);

            $durationSeconds = ($intervalBlock->distance_meters / 1000) * $pace;

            return $durationSeconds / 60;
        }

        return 0.0;
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

    /**
     * Format description for interval block load.
     */
    protected function formatIntervalDescription(
        WorkoutBlock $block,
        IntervalBlock $intervalBlock,
        string $activity
    ): string {
        $parts = [];

        if ($block->label) {
            $parts[] = $block->label;
        } else {
            $parts[] = ucfirst($activity);
        }

        if ($intervalBlock->duration_seconds !== null) {
            $minutes = floor($intervalBlock->duration_seconds / 60);
            $seconds = $intervalBlock->duration_seconds % 60;

            if ($seconds > 0) {
                $parts[] = "{$minutes}:{$seconds}";
            } else {
                $parts[] = "{$minutes}min";
            }
        } elseif ($intervalBlock->distance_meters !== null) {
            $parts[] = ($intervalBlock->distance_meters / 1000).'km';
        }

        if ($intervalBlock->intensity !== null) {
            $parts[] = $intervalBlock->intensity->label();
        }

        return implode(' - ', $parts);
    }

    /**
     * @param  array<string, array{total: float, sources: array<int, array{description: string, load: float}>}>  $loads
     */
    protected function processExerciseGroupBlock(
        WorkoutBlock $block,
        array &$loads,
        float $repeatMultiplier
    ): void {
        $exerciseGroup = $block->blockable;

        if (! $exerciseGroup instanceof ExerciseGroup) {
            return;
        }

        $exerciseGroup->load('entries.exercise.muscleLoads');

        foreach ($exerciseGroup->entries as $entry) {
            $volume = $entry->sets * ($entry->reps ?? 1) * $exerciseGroup->rounds;
            $effort = $entry->rpe_target !== null ? ($entry->rpe_target / 10) : 0.6;

            foreach ($entry->exercise->muscleLoads as $muscleLoad) {
                $load = $volume * $effort * $muscleLoad->load_factor * $repeatMultiplier;

                if ($load <= 0) {
                    continue;
                }

                $muscleKey = $muscleLoad->muscle_group->value;

                if (! isset($loads[$muscleKey])) {
                    $loads[$muscleKey] = ['total' => 0.0, 'sources' => []];
                }

                $loads[$muscleKey]['total'] += $load;
                $loads[$muscleKey]['sources'][] = [
                    'description' => $this->formatExerciseDescription($entry, $exerciseGroup),
                    'load' => $load,
                ];
            }
        }
    }

    /**
     * Format description for exercise entry load.
     */
    protected function formatExerciseDescription(
        \App\Models\ExerciseEntry $entry,
        ExerciseGroup $group
    ): string {
        $parts = [$entry->exercise->name];

        $totalSets = $entry->sets * $group->rounds;
        $parts[] = "{$totalSets} sets";

        if ($entry->reps !== null) {
            $parts[] = "{$entry->reps} reps";
        }

        if ($entry->weight_kg !== null) {
            $parts[] = "{$entry->weight_kg}kg";
        }

        return implode(' - ', $parts);
    }

    /**
     * @param  array<string, array{total: float, sources: array<int, array{description: string, load: float}>}>  $loads
     */
    protected function processGroupBlock(
        WorkoutBlock $block,
        Workout $workout,
        array &$loads,
        float $repeatMultiplier
    ): void {
        foreach ($block->children as $child) {
            $this->processBlock($child, $workout, $loads, $repeatMultiplier);
        }
    }
}
