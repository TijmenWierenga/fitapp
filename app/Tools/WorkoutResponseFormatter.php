<?php

namespace App\Tools;

use App\Domain\Workload\Calculators\DurationEstimator;
use App\Domain\Workload\PlannedBlockMapper;
use App\Models\Block;
use App\Models\BlockExercise;
use App\Models\CardioExercise;
use App\Models\DurationExercise;
use App\Models\Section;
use App\Models\StrengthExercise;
use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutPainScore;

class WorkoutResponseFormatter
{
    /**
     * @return array<string, mixed>
     */
    public static function format(Workout $workout, User $user): array
    {
        $workout->loadMissing('sections.blocks.exercises.exerciseable', 'painScores.injury');

        $estimatedDuration = (new DurationEstimator)->estimate(PlannedBlockMapper::fromWorkout($workout));

        $data = self::filterNulls([
            'id' => $workout->id,
            'name' => $workout->name,
            'activity' => $workout->activity->value,
            'scheduled_at' => $user->toUserTimezone($workout->scheduled_at)->toIso8601String(),
            'completed' => $workout->isCompleted(),
            'completed_at' => $workout->completed_at ? $user->toUserTimezone($workout->completed_at)->toIso8601String() : null,
            'estimated_duration' => $estimatedDuration,
            'rpe' => $workout->rpe,
            'feeling' => $workout->feeling,
            'notes' => $workout->notes,
            'sections' => $workout->sections->map(fn (Section $section): array => self::formatSection($section))->toArray(),
        ]);

        if ($workout->painScores->isNotEmpty()) {
            $data['pain_scores'] = $workout->painScores->map(fn (WorkoutPainScore $ps): array => [
                'injury_id' => $ps->injury_id,
                'body_part' => $ps->injury->body_part->value,
                'injury_type' => $ps->injury->injury_type->value,
                'pain_score' => $ps->pain_score,
                'pain_label' => WorkoutPainScore::getPainLabel($ps->pain_score),
            ])->toArray();
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    protected static function formatSection(Section $section): array
    {
        return self::filterNulls([
            'name' => $section->name,
            'notes' => $section->notes,
            'blocks' => $section->blocks->map(fn (Block $block): array => self::formatBlock($block))->toArray(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected static function formatBlock(Block $block): array
    {
        return self::filterNulls([
            'block_type' => $block->block_type->value,
            'rounds' => $block->rounds,
            'rest_between_exercises' => $block->rest_between_exercises,
            'rest_between_rounds' => $block->rest_between_rounds,
            'time_cap' => $block->time_cap,
            'work_interval' => $block->work_interval,
            'rest_interval' => $block->rest_interval,
            'notes' => $block->notes,
            'exercises' => $block->exercises->map(fn (BlockExercise $exercise): array => self::formatExercise($exercise))->toArray(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected static function formatExercise(BlockExercise $exercise): array
    {
        $data = [
            'name' => $exercise->name,
            'exercise_id' => $exercise->exercise_id,
            'notes' => $exercise->notes,
        ];

        $exerciseable = $exercise->exerciseable;

        if ($exerciseable instanceof StrengthExercise) {
            $data['target_sets'] = $exerciseable->target_sets;
            $data['target_reps_min'] = $exerciseable->target_reps_min;
            $data['target_reps_max'] = $exerciseable->target_reps_max;
            $data['target_weight'] = $exerciseable->target_weight;
            $data['target_rpe'] = $exerciseable->target_rpe;
            $data['target_tempo'] = $exerciseable->target_tempo;
            $data['rest_after'] = $exerciseable->rest_after;
        } elseif ($exerciseable instanceof CardioExercise) {
            $data['target_duration'] = $exerciseable->target_duration;
            $data['target_distance'] = $exerciseable->target_distance;
            $data['target_pace_min'] = $exerciseable->target_pace_min;
            $data['target_pace_max'] = $exerciseable->target_pace_max;
            $data['target_heart_rate_zone'] = $exerciseable->target_heart_rate_zone;
            $data['target_heart_rate_min'] = $exerciseable->target_heart_rate_min;
            $data['target_heart_rate_max'] = $exerciseable->target_heart_rate_max;
            $data['target_power'] = $exerciseable->target_power;
        } elseif ($exerciseable instanceof DurationExercise) {
            $data['target_duration'] = $exerciseable->target_duration;
            $data['target_rpe'] = $exerciseable->target_rpe;
        }

        return self::filterNulls($data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected static function filterNulls(array $data): array
    {
        return array_filter($data, fn (mixed $value): bool => $value !== null);
    }
}
