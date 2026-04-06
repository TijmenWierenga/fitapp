<?php

declare(strict_types=1);

namespace App\Actions;

use App\Actions\Garmin\ParsedActivityHelper;
use App\DataTransferObjects\Fit\FitImportContext;
use App\DataTransferObjects\Workout\SectionData;
use App\Enums\Workout\Activity;
use App\Models\ExerciseSet;
use App\Models\FitImport;
use App\Models\User;
use App\Models\Workout;
use App\Support\Workout\PaceConverter;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FinalizeGarminImport
{
    public function __construct(
        private CreateStructuredWorkout $createWorkout,
    ) {}

    /**
     * @param  Collection<int, SectionData>  $sections
     */
    public function execute(
        User $user,
        FitImportContext $context,
        Collection $sections,
        string $name,
        Activity $activity,
        CarbonImmutable $scheduledAt,
        ?string $notes,
        ?int $rpe,
        ?int $feeling,
    ): Workout {
        return DB::transaction(function () use ($user, $context, $sections, $name, $activity, $scheduledAt, $notes, $rpe, $feeling): Workout {
            $workout = $this->createWorkout->execute(
                user: $user,
                name: $name,
                activity: $activity,
                scheduledAt: $scheduledAt,
                notes: $notes,
                sections: $sections,
            );

            $this->populateExerciseSets($workout, $context);
            $this->setSessionSummary($workout, $context);
            $this->markCompleted($workout, $scheduledAt, $rpe, $feeling);
            $this->storeFitImport($user, $workout, $context);

            return $workout->fresh(['sections.blocks.exercises.exerciseable']);
        });
    }

    private function populateExerciseSets(Workout $workout, FitImportContext $context): void
    {
        $parsed = $context->parsedActivity;
        $workout->loadMissing('sections.blocks.exercises');

        $hasSets = count($parsed->sets) > 0;
        $hasCardioLaps = collect($parsed->laps)->contains(fn ($lap) => ($lap->totalDistance ?? 0) > 0);

        if ($hasSets) {
            $strengthExercises = $workout->sections
                ->flatMap(fn ($s) => $s->blocks)
                ->filter(fn ($b) => $b->block_type !== \App\Enums\Workout\BlockType::DistanceDuration)
                ->flatMap(fn ($b) => $b->exercises);

            $this->populateStrengthSets($strengthExercises, $parsed);
        }

        if ($hasCardioLaps || ! $hasSets) {
            $cardioExercises = $workout->sections
                ->flatMap(fn ($s) => $s->blocks)
                ->filter(fn ($b) => $b->block_type === \App\Enums\Workout\BlockType::DistanceDuration)
                ->flatMap(fn ($b) => $b->exercises);

            $this->populateCardioSets($cardioExercises, $parsed);
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<int, \App\Models\BlockExercise>  $blockExercises
     */
    private function populateStrengthSets(
        \Illuminate\Support\Collection $blockExercises,
        \App\DataTransferObjects\Fit\ParsedActivity $parsed,
    ): void {
        $activeSets = collect($parsed->sets)->filter->isActive();
        $groups = ParsedActivityHelper::groupSetsByExercise($activeSets);

        $exerciseIndex = 0;

        foreach ($groups as $group) {
            if (! isset($blockExercises[$exerciseIndex])) {
                $exerciseIndex++;

                continue;
            }

            $blockExercise = $blockExercises[$exerciseIndex];

            foreach ($group['sets'] as $setIndex => $set) {
                ExerciseSet::create([
                    'block_exercise_id' => $blockExercise->id,
                    'set_number' => $setIndex + 1,
                    'reps' => $set->repetitions,
                    'weight' => $set->weight,
                    'set_duration' => $set->duration,
                ]);
            }

            $exerciseIndex++;
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<int, \App\Models\BlockExercise>  $blockExercises
     */
    private function populateCardioSets(
        \Illuminate\Support\Collection $blockExercises,
        \App\DataTransferObjects\Fit\ParsedActivity $parsed,
    ): void {
        if ($blockExercises->isEmpty()) {
            return;
        }

        $primaryExercise = $blockExercises->first();

        foreach ($parsed->laps as $index => $lap) {
            ExerciseSet::create([
                'block_exercise_id' => $primaryExercise->id,
                'set_number' => $index + 1,
                'distance' => $lap->totalDistance,
                'duration' => $lap->totalElapsedTime,
                'avg_heart_rate' => $lap->avgHeartRate,
                'max_heart_rate' => $lap->maxHeartRate,
                'avg_pace' => PaceConverter::fromFitSpeed($lap->avgSpeed),
                'avg_power' => $lap->avgPower,
                'max_power' => $lap->maxPower,
                'avg_cadence' => $lap->avgCadence,
                'total_ascent' => $lap->totalAscent,
            ]);
        }
    }

    private function setSessionSummary(Workout $workout, FitImportContext $context): void
    {
        $parsed = $context->parsedActivity;

        $workout->update([
            'total_duration' => $parsed->session->totalElapsedTime,
            'total_distance' => $parsed->session->totalDistance,
            'total_calories' => $parsed->session->totalCalories,
            'avg_heart_rate' => $parsed->session->avgHeartRate,
            'max_heart_rate' => $parsed->session->maxHeartRate,
            'source' => 'garmin_fit',
        ]);
    }

    private function markCompleted(Workout $workout, CarbonImmutable $scheduledAt, ?int $rpe, ?int $feeling): void
    {
        $workout->update([
            'completed_at' => $scheduledAt,
            'rpe' => $rpe,
            'feeling' => $feeling,
        ]);
    }

    private function storeFitImport(User $user, Workout $workout, FitImportContext $context): void
    {
        FitImport::create([
            'user_id' => $user->id,
            'workout_id' => $workout->id,
            'raw_data' => base64_encode($context->rawBytes),
            'imported_at' => now(),
        ]);
    }
}
