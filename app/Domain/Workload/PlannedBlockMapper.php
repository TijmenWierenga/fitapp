<?php

declare(strict_types=1);

namespace App\Domain\Workload;

use App\Domain\Workload\ValueObjects\PlannedBlock;
use App\Models\CardioExercise;
use App\Models\DurationExercise;
use App\Models\Section;
use App\Models\Workout;

class PlannedBlockMapper
{
    /**
     * @return array<PlannedBlock>
     */
    public static function fromWorkout(Workout $workout): array
    {
        $blocks = [];

        foreach ($workout->sections as $section) {
            foreach ($section->blocks as $block) {
                $exerciseDurations = $block->exercises->map(
                    fn ($exercise): ?int => self::extractDuration($exercise->exerciseable),
                )->all();

                $blocks[] = new PlannedBlock(
                    blockType: $block->block_type,
                    rounds: $block->rounds,
                    restBetweenExercises: $block->rest_between_exercises,
                    restBetweenRounds: $block->rest_between_rounds,
                    timeCap: $block->time_cap,
                    workInterval: $block->work_interval,
                    restInterval: $block->rest_interval,
                    exerciseDurations: $exerciseDurations,
                );
            }
        }

        return $blocks;
    }

    /**
     * @return array<PlannedBlock>
     */
    public static function fromSection(Section $section): array
    {
        $blocks = [];

        foreach ($section->blocks as $block) {
            $exerciseDurations = $block->exercises->map(
                fn ($exercise): ?int => self::extractDuration($exercise->exerciseable),
            )->all();

            $blocks[] = new PlannedBlock(
                blockType: $block->block_type,
                rounds: $block->rounds,
                restBetweenExercises: $block->rest_between_exercises,
                restBetweenRounds: $block->rest_between_rounds,
                timeCap: $block->time_cap,
                workInterval: $block->work_interval,
                restInterval: $block->rest_interval,
                exerciseDurations: $exerciseDurations,
            );
        }

        return $blocks;
    }

    private static function extractDuration(mixed $exerciseable): ?int
    {
        if ($exerciseable instanceof CardioExercise || $exerciseable instanceof DurationExercise) {
            return $exerciseable->target_duration;
        }

        return null;
    }
}
