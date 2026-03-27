<?php

declare(strict_types=1);

namespace App\Actions;

use App\Actions\Garmin\BuildWorkoutFromActivity;
use App\Actions\Garmin\MergeActualsIntoWorkout;
use App\Actions\Garmin\ParsedActivityHelper;
use App\Actions\Garmin\SportMapper;
use App\DataTransferObjects\Fit\ImportResult;
use App\Models\ExerciseSet;
use App\Models\User;
use App\Models\Workout;
use App\Support\Fit\Decode\FitActivityParser;
use App\Support\Workout\PaceConverter;
use Illuminate\Support\Facades\DB;

class ImportGarminActivity
{
    public function __construct(
        private FitActivityParser $parser,
        private CreateStructuredWorkout $createWorkout,
        private BuildWorkoutFromActivity $builder,
        private MergeActualsIntoWorkout $merger,
    ) {}

    /**
     * @param  array<int, int>  $exerciseMappings  Map of exercise group index → Exercise model ID
     */
    public function execute(
        User $user,
        string $fitData,
        ?Workout $existingWorkout = null,
        ?int $rpe = null,
        ?int $feeling = null,
        array $exerciseMappings = [],
    ): ImportResult {
        $parsed = $this->parser->parse($fitData);

        $duplicateWarnings = $this->checkForDuplicates($user, $parsed);

        return DB::transaction(function () use ($user, $parsed, $existingWorkout, $rpe, $feeling, $duplicateWarnings, $exerciseMappings): ImportResult {
            if ($existingWorkout !== null) {
                $result = $this->mergeIntoExisting($existingWorkout, $parsed, $rpe, $feeling, $exerciseMappings);
            } else {
                $result = $this->createNew($user, $parsed, $rpe, $feeling, $exerciseMappings);
            }

            return new ImportResult(
                workout: $result->workout,
                matchedExercises: $result->matchedExercises,
                unmatchedExercises: $result->unmatchedExercises,
                warnings: [...$duplicateWarnings, ...$result->warnings],
            );
        });
    }

    /**
     * @return list<string>
     */
    private function checkForDuplicates(User $user, \App\DataTransferObjects\Fit\ParsedActivity $parsed): array
    {
        $activity = SportMapper::toActivity($parsed->session->sport, $parsed->session->subSport);

        $duplicate = Workout::query()
            ->where('user_id', $user->id)
            ->where('activity', $activity)
            ->where('source', 'garmin_fit')
            ->whereDate('scheduled_at', $parsed->session->startTime->toDateString())
            ->first();

        if ($duplicate === null) {
            return [];
        }

        return ["Possible duplicate: workout '{$duplicate->name}' on {$duplicate->scheduled_at->format('M j, Y')} was already imported from Garmin."];
    }

    /**
     * @param  array<int, int>  $exerciseMappings
     */
    private function createNew(
        User $user,
        \App\DataTransferObjects\Fit\ParsedActivity $parsed,
        ?int $rpe,
        ?int $feeling,
        array $exerciseMappings = [],
    ): ImportResult {
        $activity = SportMapper::toActivity($parsed->session->sport, $parsed->session->subSport);
        $result = $this->builder->execute($parsed, $exerciseMappings);

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

    /**
     * @param  array<int, int>  $exerciseMappings
     */
    private function mergeIntoExisting(
        Workout $workout,
        \App\DataTransferObjects\Fit\ParsedActivity $parsed,
        ?int $rpe,
        ?int $feeling,
        array $exerciseMappings = [],
    ): ImportResult {
        $result = $this->merger->execute($workout, $parsed, $exerciseMappings);

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
        $detectedBlocks = ParsedActivityHelper::detectBlocks($groups);

        $exerciseIndex = 0;

        foreach ($detectedBlocks as $detected) {
            foreach ($detected['exercises'] as $exerciseInfo) {
                if (! isset($blockExercises[$exerciseIndex])) {
                    $exerciseIndex++;

                    continue;
                }

                $blockExercise = $blockExercises[$exerciseIndex];

                foreach ($exerciseInfo['sets'] as $setIndex => $set) {
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
