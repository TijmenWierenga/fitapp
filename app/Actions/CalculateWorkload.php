<?php

namespace App\Actions;

use App\DataTransferObjects\Workload\MuscleGroupWorkload;
use App\DataTransferObjects\Workload\WorkloadSummary;
use App\Models\BlockExercise;
use App\Models\CardioExercise;
use App\Models\DurationExercise;
use App\Models\MuscleGroup;
use App\Models\StrengthExercise;
use App\Models\User;
use Carbon\CarbonImmutable;

class CalculateWorkload
{
    private const int CHRONIC_WINDOW_DAYS = 28;

    private const int ACUTE_WINDOW_DAYS = 7;

    private const float DEFAULT_RPE = 5.0;

    private const int DEFAULT_HR_ZONE = 3;

    public function execute(User $user, ?CarbonImmutable $asOf = null): WorkloadSummary
    {
        $asOf ??= CarbonImmutable::now();
        $chronicStart = $asOf->subDays(self::CHRONIC_WINDOW_DAYS);
        $acuteStart = $asOf->subDays(self::ACUTE_WINDOW_DAYS);

        $workouts = $user->workouts()
            ->completed()
            ->where('completed_at', '>=', $chronicStart)
            ->where('completed_at', '<=', $asOf)
            ->with('sections.blocks.exercises.exerciseable', 'sections.blocks.exercises.exercise.muscleGroups')
            ->get();

        $muscleGroups = MuscleGroup::all()->keyBy('id');
        $unlinkedCount = 0;

        /** @var array<int, array{acute: float, chronic: float}> */
        $loads = [];
        foreach ($muscleGroups as $mg) {
            $loads[$mg->id] = ['acute' => 0.0, 'chronic' => 0.0];
        }

        foreach ($workouts as $workout) {
            $isAcute = $workout->completed_at >= $acuteStart;

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

                        $volume = $this->calculateVolume($blockExercise, $block);

                        foreach ($exercise->muscleGroups as $muscleGroup) {
                            $loadFactor = (float) $muscleGroup->pivot->load_factor;
                            $load = $volume * $loadFactor;

                            $loads[$muscleGroup->id]['chronic'] += $load;

                            if ($isAcute) {
                                $loads[$muscleGroup->id]['acute'] += $load;
                            }
                        }
                    }
                }
            }
        }

        $chronicWeeks = self::CHRONIC_WINDOW_DAYS / 7;

        $workloads = $muscleGroups
            ->map(fn (MuscleGroup $mg): MuscleGroupWorkload => new MuscleGroupWorkload(
                muscleGroupName: $mg->name,
                muscleGroupLabel: $mg->label,
                bodyPart: $mg->body_part->value,
                acuteLoad: $loads[$mg->id]['acute'],
                chronicLoad: $loads[$mg->id]['chronic'] / $chronicWeeks,
            ))
            ->filter(fn (MuscleGroupWorkload $w): bool => $w->acuteLoad > 0 || $w->chronicLoad > 0)
            ->values();

        $activeInjuries = $user->injuries()
            ->active()
            ->get()
            ->map(fn ($injury): array => [
                'id' => $injury->id,
                'body_part' => $injury->body_part->value,
                'injury_type' => $injury->injury_type->value,
                'started_at' => $injury->started_at->toDateString(),
            ]);

        return new WorkloadSummary(
            muscleGroups: $workloads,
            activeInjuries: $activeInjuries,
            unlinkedExerciseCount: $unlinkedCount,
        );
    }

    private function calculateVolume(BlockExercise $blockExercise, \App\Models\Block $block): float
    {
        $exerciseable = $blockExercise->exerciseable;

        return match (true) {
            $exerciseable instanceof StrengthExercise => $this->strengthVolume($exerciseable, $block),
            $exerciseable instanceof CardioExercise => $this->cardioVolume($exerciseable),
            $exerciseable instanceof DurationExercise => $this->durationVolume($exerciseable),
            default => 0.0,
        };
    }

    private function strengthVolume(StrengthExercise $exercise, \App\Models\Block $block): float
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
