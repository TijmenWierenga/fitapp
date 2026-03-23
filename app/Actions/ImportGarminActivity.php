<?php

declare(strict_types=1);

namespace App\Actions;

use App\DataTransferObjects\Fit\ImportResult;
use App\Models\ExerciseSet;
use App\Models\User;
use App\Models\Workout;
use App\Support\Fit\Decode\BuildWorkoutFromActivity;
use App\Support\Fit\Decode\FitActivityParser;
use App\Support\Fit\Decode\MergeActualsIntoWorkout;
use App\Support\Fit\SportMapper;
use Illuminate\Support\Facades\DB;

class ImportGarminActivity
{
    public function __construct(
        private FitActivityParser $parser,
        private CreateStructuredWorkout $createWorkout,
        private BuildWorkoutFromActivity $builder,
        private MergeActualsIntoWorkout $merger,
    ) {}

    public function execute(
        User $user,
        string $fitData,
        ?Workout $existingWorkout = null,
        ?int $rpe = null,
        ?int $feeling = null,
    ): ImportResult {
        $parsed = $this->parser->parse($fitData);

        return DB::transaction(function () use ($user, $parsed, $existingWorkout, $rpe, $feeling): ImportResult {
            if ($existingWorkout !== null) {
                return $this->mergeIntoExisting($existingWorkout, $parsed, $rpe, $feeling);
            }

            return $this->createNew($user, $parsed, $rpe, $feeling);
        });
    }

    private function createNew(
        User $user,
        \App\DataTransferObjects\Fit\ParsedActivity $parsed,
        ?int $rpe,
        ?int $feeling,
    ): ImportResult {
        $activity = SportMapper::toActivity($parsed->session->sport, $parsed->session->subSport);
        $result = $this->builder->execute($parsed);

        $workoutName = $parsed->session->workoutName ?? $activity->label();

        $workout = $this->createWorkout->execute(
            user: $user,
            name: $workoutName,
            activity: $activity,
            scheduledAt: $parsed->session->startTime,
            notes: null,
            sections: $result['sections'],
        );

        $this->populateExerciseSets($workout, $parsed);
        $this->setSessionSummary($workout, $parsed);
        $this->markCompleted($workout, $rpe, $feeling);

        return new ImportResult(
            workout: $workout->fresh(['sections.blocks.exercises.exerciseable']),
            matchedExercises: $result['matched'],
            unmatchedExercises: $result['unmatched'],
            warnings: $result['warnings'],
        );
    }

    private function mergeIntoExisting(
        Workout $workout,
        \App\DataTransferObjects\Fit\ParsedActivity $parsed,
        ?int $rpe,
        ?int $feeling,
    ): ImportResult {
        $result = $this->merger->execute($workout, $parsed);

        $this->setSessionSummary($workout, $parsed);

        if (! $workout->isCompleted()) {
            $this->markCompleted($workout, $rpe, $feeling);
        }

        return new ImportResult(
            workout: $workout->fresh(['sections.blocks.exercises.exerciseable']),
            matchedExercises: $result['matched'],
            unmatchedExercises: $result['unmatched'],
            warnings: $result['warnings'],
        );
    }

    private function populateExerciseSets(
        Workout $workout,
        \App\DataTransferObjects\Fit\ParsedActivity $parsed,
    ): void {
        $workout->loadMissing('sections.blocks.exercises');

        $blockExercises = $workout->sections
            ->flatMap(fn ($s) => $s->blocks)
            ->flatMap(fn ($b) => $b->exercises);

        if (count($parsed->sets) > 0) {
            $this->populateStrengthSets($blockExercises, $parsed);
        } else {
            $this->populateCardioSets($blockExercises, $parsed);
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
        $groups = [];
        $currentGroup = null;

        foreach ($activeSets as $set) {
            $category = $set->exerciseCategory ?? 0;
            $name = $set->exerciseName ?? 0;
            $key = "{$category}:{$name}";

            if ($currentGroup === null || $currentGroup['key'] !== $key) {
                if ($currentGroup !== null) {
                    $groups[] = $currentGroup;
                }
                $currentGroup = ['key' => $key, 'sets' => []];
            }

            $currentGroup['sets'][] = $set;
        }

        if ($currentGroup !== null) {
            $groups[] = $currentGroup;
        }

        foreach ($groups as $index => $group) {
            if (! isset($blockExercises[$index])) {
                continue;
            }

            $blockExercise = $blockExercises[$index];

            foreach ($group['sets'] as $setIndex => $set) {
                ExerciseSet::create([
                    'block_exercise_id' => $blockExercise->id,
                    'set_number' => $setIndex + 1,
                    'reps' => $set->repetitions,
                    'weight' => $set->weight,
                    'set_duration' => $set->duration,
                ]);
            }
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
            $avgPace = null;

            if ($lap->avgSpeed !== null && $lap->avgSpeed > 0) {
                $avgPace = (int) round(1_000_000 / $lap->avgSpeed);
            }

            ExerciseSet::create([
                'block_exercise_id' => $primaryExercise->id,
                'set_number' => $index + 1,
                'distance' => $lap->totalDistance,
                'duration' => $lap->totalElapsedTime,
                'avg_heart_rate' => $lap->avgHeartRate,
                'max_heart_rate' => $lap->maxHeartRate,
                'avg_pace' => $avgPace,
                'avg_power' => $lap->avgPower,
                'max_power' => $lap->maxPower,
                'avg_cadence' => $lap->avgCadence,
                'total_ascent' => $lap->totalAscent,
            ]);
        }
    }

    private function setSessionSummary(Workout $workout, \App\DataTransferObjects\Fit\ParsedActivity $parsed): void
    {
        $workout->update([
            'total_duration' => $parsed->session->totalElapsedTime,
            'total_distance' => $parsed->session->totalDistance,
            'total_calories' => $parsed->session->totalCalories,
            'avg_heart_rate' => $parsed->session->avgHeartRate,
            'max_heart_rate' => $parsed->session->maxHeartRate,
            'source' => 'garmin_fit',
        ]);
    }

    private function markCompleted(Workout $workout, ?int $rpe, ?int $feeling): void
    {
        $workout->update([
            'completed_at' => $workout->completed_at ?? now(),
            'rpe' => $rpe,
            'feeling' => $feeling,
        ]);
    }
}
