<?php

declare(strict_types=1);

namespace App\Actions;

use App\Actions\Garmin\ParsedActivityHelper;
use App\Actions\Garmin\SportMapper;
use App\DataTransferObjects\Fit\ParsedActivity;
use App\Models\ExerciseSet;
use App\Models\FitImport;
use App\Models\User;
use App\Models\Workout;
use App\Support\Fit\Decode\FitActivityParser;
use App\Support\Workout\PaceConverter;
use Illuminate\Support\Facades\DB;

class ImportFitToWorkout
{
    public function __construct(
        private FitActivityParser $parser,
    ) {}

    /**
     * @return array{warnings: list<string>}
     */
    public function execute(
        User $user,
        Workout $workout,
        string $fitData,
        ?int $rpe = null,
        ?int $feeling = null,
    ): array {
        $parsed = $this->parser->parse($fitData);
        $warnings = $this->checkForDuplicates($user, $parsed);
        $mismatchWarning = $this->detectMismatch($workout, $parsed);

        if ($mismatchWarning !== null) {
            $warnings[] = $mismatchWarning;
        }

        DB::transaction(function () use ($user, $workout, $parsed, $fitData, $rpe, $feeling): void {
            $this->populateExerciseSets($workout, $parsed);
            $this->setSessionSummary($workout, $parsed);
            $this->markCompleted($workout, $rpe, $feeling);
            $this->storeFitImport($user, $workout, $fitData);
        });

        return ['warnings' => $warnings];
    }

    private function detectMismatch(Workout $workout, ParsedActivity $parsed): ?string
    {
        $activeSets = collect($parsed->sets)->filter->isActive();

        if ($activeSets->isEmpty()) {
            return null;
        }

        $groups = ParsedActivityHelper::groupSetsByExercise($activeSets);
        $fitExerciseCount = count($groups);

        $workout->loadMissing('sections.blocks.exercises');
        $plannedCount = $workout->sections
            ->flatMap(fn ($s) => $s->blocks)
            ->filter(fn ($b) => $b->block_type !== \App\Enums\Workout\BlockType::DistanceDuration)
            ->flatMap(fn ($b) => $b->exercises)
            ->count();

        if ($fitExerciseCount !== $plannedCount) {
            return "FIT file has {$fitExerciseCount} exercises, planned workout has {$plannedCount}.";
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function checkForDuplicates(User $user, ParsedActivity $parsed): array
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

    private function populateExerciseSets(Workout $workout, ParsedActivity $parsed): void
    {
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
        ParsedActivity $parsed,
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
        ParsedActivity $parsed,
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

    private function setSessionSummary(Workout $workout, ParsedActivity $parsed): void
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

    private function storeFitImport(User $user, Workout $workout, string $fitData): void
    {
        FitImport::create([
            'user_id' => $user->id,
            'workout_id' => $workout->id,
            'raw_data' => base64_encode($fitData),
            'imported_at' => now(),
        ]);
    }
}
