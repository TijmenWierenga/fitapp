<?php

declare(strict_types=1);

namespace App\Actions;

use App\Domain\Workload\Calculators\ExerciseStrengthHistoryCalculator;
use App\Domain\Workload\Enums\HistoryRange;
use App\Domain\Workload\Results\ExerciseStrengthHistoryResult;
use App\Domain\Workload\ValueObjects\StrengthRecord;
use App\Models\Exercise;
use App\Models\StrengthExercise;
use App\Models\User;
use Carbon\CarbonImmutable;
use DateTimeImmutable;

class GetExerciseStrengthHistory
{
    public function __construct(
        private ExerciseStrengthHistoryCalculator $calculator,
    ) {}

    public function execute(
        User $user,
        int $exerciseId,
        HistoryRange $range,
        ?CarbonImmutable $asOf = null,
    ): ExerciseStrengthHistoryResult {
        $asOf = $asOf ?? CarbonImmutable::now();
        $exercise = Exercise::findOrFail($exerciseId);

        $from = $range->days() !== null
            ? $asOf->subDays($range->days())
            : null;

        $workouts = $user->workouts()
            ->whereNotNull('completed_at')
            ->when($from, fn ($q) => $q->where('completed_at', '>=', $from))
            ->where('completed_at', '<=', $asOf)
            ->whereHas('sections.blocks.exercises', fn ($q) => $q->where('exercise_id', $exerciseId))
            ->with([
                'sections.blocks.exercises' => fn ($q) => $q->where('exercise_id', $exerciseId),
                'sections.blocks.exercises.exerciseable',
            ])
            ->get();

        $records = [];

        foreach ($workouts as $workout) {
            foreach ($workout->sections as $section) {
                foreach ($section->blocks as $block) {
                    foreach ($block->exercises as $blockExercise) {
                        $exerciseable = $blockExercise->exerciseable;

                        if (! $exerciseable instanceof StrengthExercise) {
                            continue;
                        }

                        if ($exerciseable->target_weight === null || (float) $exerciseable->target_weight <= 0) {
                            continue;
                        }

                        $reps = $exerciseable->target_reps_max ?? $exerciseable->target_reps_min ?? 1;

                        $records[] = new StrengthRecord(
                            exerciseId: $blockExercise->exercise_id,
                            exerciseName: $exercise->name,
                            performedAt: new DateTimeImmutable($workout->completed_at->toDateTimeString()),
                            weight: (float) $exerciseable->target_weight,
                            reps: $reps,
                            sets: $exerciseable->target_sets ?? 1,
                        );
                    }
                }
            }
        }

        return $this->calculator->calculate($records, $exerciseId, $exercise->name);
    }
}
