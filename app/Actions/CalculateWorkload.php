<?php

namespace App\Actions;

use App\DataTransferObjects\Workload\WorkloadSummary;
use App\Domain\Workload\Calculators\MuscleGroupVolumeCalculator;
use App\Domain\Workload\Calculators\SessionLoadCalculator;
use App\Domain\Workload\Calculators\StrengthProgressionCalculator;
use App\Domain\Workload\ValueObjects\CompletedSession;
use App\Domain\Workload\ValueObjects\DateRange;
use App\Domain\Workload\ValueObjects\MuscleGroupMapping;
use App\Domain\Workload\ValueObjects\PerformedExercise;
use App\Domain\Workload\ValueObjects\StrengthRecord;
use App\Models\BlockExercise;
use App\Models\StrengthExercise;
use App\Models\User;
use App\Models\Workout;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use Illuminate\Support\Collection;

class CalculateWorkload
{
    private const int DATA_WINDOW_DAYS = 56;

    private const int WEEKS = 4;

    public function __construct(
        private SessionLoadCalculator $sessionLoadCalculator,
        private MuscleGroupVolumeCalculator $muscleGroupVolumeCalculator,
        private StrengthProgressionCalculator $strengthProgressionCalculator,
    ) {}

    public function execute(User $user, ?CarbonImmutable $asOf = null): WorkloadSummary
    {
        $asOf = $asOf ?? CarbonImmutable::now();
        $windowStart = $asOf->subDays(self::DATA_WINDOW_DAYS);

        $workouts = $this->completedWorkoutsInWindow($user, $windowStart, $asOf);

        $weekRanges = $this->buildWeekRanges($asOf);
        $currentWeek = $weekRanges[0];
        $previousWeeks = array_slice($weekRanges, 1);

        $sessions = $this->mapToSessions($workouts);
        $sessionLoad = ! empty($sessions)
            ? $this->sessionLoadCalculator->calculate($sessions, $currentWeek, $previousWeeks)
            : null;

        [$exercises, $unlinkedCount] = $this->mapToPerformedExercises($workouts);
        $muscleGroupVolume = $this->muscleGroupVolumeCalculator->calculate($exercises, $weekRanges);

        $strengthRecords = $this->mapToStrengthRecords($workouts);
        $currentPeriod = new DateRange(
            from: new DateTimeImmutable($asOf->subDays(27)->toDateTimeString()),
            to: new DateTimeImmutable($asOf->toDateTimeString()),
        );
        $previousPeriod = new DateRange(
            from: new DateTimeImmutable($asOf->subDays(55)->toDateTimeString()),
            to: new DateTimeImmutable($asOf->subDays(28)->toDateTimeString()),
        );
        $strengthProgression = $this->strengthProgressionCalculator->calculate(
            $strengthRecords,
            $currentPeriod,
            $previousPeriod,
        );

        return new WorkloadSummary(
            sessionLoad: $sessionLoad,
            muscleGroupVolume: collect($muscleGroupVolume),
            strengthProgression: $strengthProgression,
            activeInjuries: $this->activeInjuries($user),
            unlinkedExerciseCount: $unlinkedCount,
            dataSpanDays: $this->calculateDataSpanDays($workouts, $asOf),
        );
    }

    /**
     * @return Collection<int, Workout>
     */
    private function completedWorkoutsInWindow(User $user, CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        return $user->workouts()
            ->completedBetween($from, $to)
            ->with(['sections.blocks.exercises.exerciseable', 'sections.blocks.exercises.exercise.muscleGroups'])
            ->get();
    }

    /**
     * @return array<DateRange>
     */
    private function buildWeekRanges(CarbonImmutable $asOf): array
    {
        $ranges = [];

        for ($i = 0; $i < self::WEEKS; $i++) {
            $weekEnd = $asOf->subWeeks($i);
            $weekStart = $weekEnd->subDays(6);

            $ranges[] = new DateRange(
                from: new DateTimeImmutable($weekStart->startOfDay()->toDateTimeString()),
                to: new DateTimeImmutable($weekEnd->endOfDay()->toDateTimeString()),
            );
        }

        return $ranges;
    }

    /**
     * @param  Collection<int, Workout>  $workouts
     * @return array<CompletedSession>
     */
    private function mapToSessions(Collection $workouts): array
    {
        $sessions = [];

        foreach ($workouts as $workout) {
            if ($workout->duration === null || $workout->rpe === null) {
                continue;
            }

            $sessions[] = new CompletedSession(
                completedAt: new DateTimeImmutable($workout->completed_at->toDateTimeString()),
                durationMinutes: (int) ceil($workout->duration / 60),
                rpe: $workout->rpe,
            );
        }

        return $sessions;
    }

    /**
     * @param  Collection<int, Workout>  $workouts
     * @return array{array<PerformedExercise>, int}
     */
    private function mapToPerformedExercises(Collection $workouts): array
    {
        $exercises = [];
        $unlinkedCount = 0;

        foreach ($workouts as $workout) {
            foreach ($workout->sections as $section) {
                foreach ($section->blocks as $block) {
                    foreach ($block->exercises as $blockExercise) {
                        if ($blockExercise->exercise_id === null) {
                            $unlinkedCount++;

                            continue;
                        }

                        $exercise = $blockExercise->exercise;

                        if (! $exercise || $exercise->muscleGroups->isEmpty()) {
                            continue;
                        }

                        $exerciseable = $blockExercise->exerciseable;
                        $exerciseType = match (true) {
                            $exerciseable instanceof StrengthExercise => 'strength',
                            default => 'other',
                        };

                        $sets = $this->determineSets($blockExercise, $block);

                        $muscleGroups = $exercise->muscleGroups->map(
                            fn ($mg): MuscleGroupMapping => new MuscleGroupMapping(
                                muscleGroupId: $mg->id,
                                name: $mg->name,
                                label: $mg->label,
                                bodyPart: $mg->body_part->value,
                                loadFactor: (float) $mg->pivot->load_factor,
                            ),
                        )->all();

                        $exercises[] = new PerformedExercise(
                            completedAt: new DateTimeImmutable($workout->completed_at->toDateTimeString()),
                            sets: $sets,
                            exerciseType: $exerciseType,
                            muscleGroups: $muscleGroups,
                        );
                    }
                }
            }
        }

        return [$exercises, $unlinkedCount];
    }

    private function determineSets(BlockExercise $blockExercise, \App\Models\Block $block): int
    {
        $exerciseable = $blockExercise->exerciseable;

        if ($exerciseable instanceof StrengthExercise) {
            return $exerciseable->target_sets ?? $block->rounds ?? 1;
        }

        return 1;
    }

    /**
     * @param  Collection<int, Workout>  $workouts
     * @return array<StrengthRecord>
     */
    private function mapToStrengthRecords(Collection $workouts): array
    {
        $records = [];

        foreach ($workouts as $workout) {
            foreach ($workout->sections as $section) {
                foreach ($section->blocks as $block) {
                    foreach ($block->exercises as $blockExercise) {
                        if ($blockExercise->exercise_id === null) {
                            continue;
                        }

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
                            exerciseName: $blockExercise->exercise->name ?? $blockExercise->name,
                            performedAt: new DateTimeImmutable($workout->completed_at->toDateTimeString()),
                            weight: (float) $exerciseable->target_weight,
                            reps: $reps,
                        );
                    }
                }
            }
        }

        return $records;
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

    /**
     * @param  Collection<int, Workout>  $workouts
     */
    private function calculateDataSpanDays(Collection $workouts, CarbonImmutable $asOf): int
    {
        if ($workouts->isEmpty()) {
            return 0;
        }

        $earliest = $workouts->min('completed_at');

        return min((int) $earliest->diffInDays($asOf), self::DATA_WINDOW_DAYS);
    }
}
