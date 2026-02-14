<?php

namespace App\Actions;

use App\DataTransferObjects\Workload\WorkloadSummary;
use App\Models\Block;
use App\Models\BlockExercise;
use App\Models\CardioExercise;
use App\Models\DurationExercise;
use App\Models\MuscleGroup;
use App\Models\StrengthExercise;
use App\Models\User;
use App\Models\Workout;
use App\Support\Workout\LoadAccumulator;
use Carbon\CarbonImmutable;
use Generator;
use Illuminate\Support\Collection;

class CalculateWorkload
{
    private const int CHRONIC_WINDOW_DAYS = 28;

    private const int ACUTE_WINDOW_DAYS = 7;

    private const float DEFAULT_RPE = 5.0;

    private const int DEFAULT_HR_ZONE = 3;

    public function execute(User $user, ?CarbonImmutable $asOf = null): WorkloadSummary
    {
        $asOf ??= CarbonImmutable::now();
        $acuteStart = $asOf->subDays(self::ACUTE_WINDOW_DAYS);

        $workouts = $this->completedWorkoutsInWindow($user, $asOf);
        $accumulator = new LoadAccumulator(MuscleGroup::all()->keyBy('id'));

        foreach ($this->allBlockExercises($workouts) as [$blockExercise, $block, $workout]) {
            $this->distributeLoad($blockExercise, $block, $workout->completed_at >= $acuteStart, $accumulator);
        }

        return new WorkloadSummary(
            muscleGroups: $accumulator->toMuscleGroupWorkloads(self::CHRONIC_WINDOW_DAYS / 7),
            activeInjuries: $this->activeInjuries($user),
            unlinkedExerciseCount: $accumulator->unlinkedCount(),
        );
    }

    /**
     * @return Collection<int, Workout>
     */
    private function completedWorkoutsInWindow(User $user, CarbonImmutable $asOf): Collection
    {
        return $user->workouts()
            ->completedBetween($asOf->subDays(self::CHRONIC_WINDOW_DAYS), $asOf)
            ->with(['sections.blocks.exercises.exerciseable', 'sections.blocks.exercises.exercise.muscleGroups'])
            ->get();
    }

    /**
     * @param  Collection<int, Workout>  $workouts
     * @return Generator<int, array{BlockExercise, Block, Workout}>
     */
    private function allBlockExercises(Collection $workouts): Generator
    {
        foreach ($workouts as $workout) {
            foreach ($workout->sections as $section) {
                foreach ($section->blocks as $block) {
                    foreach ($block->exercises as $blockExercise) {
                        yield [$blockExercise, $block, $workout];
                    }
                }
            }
        }
    }

    private function distributeLoad(BlockExercise $blockExercise, Block $block, bool $isAcute, LoadAccumulator $accumulator): void
    {
        if ($blockExercise->exercise_id === null) {
            $accumulator->recordUnlinked();

            return;
        }

        $exercise = $blockExercise->exercise;

        if (! $exercise || $exercise->muscleGroups->isEmpty()) {
            return;
        }

        $volume = $this->calculateVolume($blockExercise, $block);

        foreach ($exercise->muscleGroups as $muscleGroup) {
            $load = $volume * (float) $muscleGroup->pivot->load_factor;
            $accumulator->addLoad($muscleGroup->id, $load, $isAcute);
        }
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function activeInjuries(User $user): Collection
    {
        return $user->injuries()
            ->active()
            ->get()
            ->map(fn ($injury): array => [
                'id' => $injury->id,
                'body_part' => $injury->body_part->value,
                'injury_type' => $injury->injury_type->value,
                'started_at' => $injury->started_at->toDateString(),
            ]);
    }

    private function calculateVolume(BlockExercise $blockExercise, Block $block): float
    {
        $exerciseable = $blockExercise->exerciseable;

        return match (true) {
            $exerciseable instanceof StrengthExercise => $this->strengthVolume($exerciseable, $block),
            $exerciseable instanceof CardioExercise => $this->cardioVolume($exerciseable),
            $exerciseable instanceof DurationExercise => $this->durationVolume($exerciseable),
            default => 0.0,
        };
    }

    private function strengthVolume(StrengthExercise $exercise, Block $block): float
    {
        $sets = $exercise->target_sets ?? $block->rounds ?? 1;
        $reps = $exercise->target_reps_max ?? $exercise->target_reps_min ?? 1;
        $rpe = (float) ($exercise->target_rpe ?? self::DEFAULT_RPE);

        return $sets * $reps * ($rpe / 10);
    }

    private function cardioVolume(CardioExercise $exercise): float
    {
        $durationMin = ($exercise->target_duration ?? 0) / 60;
        $hrZone = $exercise->target_heart_rate_zone ?? self::DEFAULT_HR_ZONE;

        return ($durationMin / 10) * ($hrZone / 5);
    }

    private function durationVolume(DurationExercise $exercise): float
    {
        $durationMin = ($exercise->target_duration ?? 0) / 60;
        $rpe = (float) ($exercise->target_rpe ?? self::DEFAULT_RPE);

        return $durationMin * ($rpe / 10);
    }
}
