<?php

declare(strict_types=1);

namespace App\Actions;

use App\Actions\Garmin\ParsedActivityHelper;
use App\DataTransferObjects\Fit\ParsedActivity;
use App\Enums\Workout\BlockType;
use App\Models\ExerciseSet;
use App\Models\Workout;
use App\Support\Workout\PaceConverter;
use Illuminate\Support\Collection;

class PopulateFitExerciseSets
{
    public function execute(Workout $workout, ParsedActivity $parsed): void
    {
        $workout->loadMissing('sections.blocks.exercises');

        $hasSets = count($parsed->sets) > 0;
        $hasCardioLaps = collect($parsed->laps)->contains(fn ($lap) => ($lap->totalDistance ?? 0) > 0);

        if ($hasSets) {
            $strengthExercises = $workout->sections
                ->flatMap(fn ($s) => $s->blocks)
                ->filter(fn ($b) => $b->block_type !== BlockType::DistanceDuration)
                ->flatMap(fn ($b) => $b->exercises);

            $this->populateStrengthSets($strengthExercises, $parsed);
        }

        if ($hasCardioLaps || ! $hasSets) {
            $cardioExercises = $workout->sections
                ->flatMap(fn ($s) => $s->blocks)
                ->filter(fn ($b) => $b->block_type === BlockType::DistanceDuration)
                ->flatMap(fn ($b) => $b->exercises);

            $this->populateCardioSets($cardioExercises, $parsed);
        }
    }

    /**
     * @param  Collection<int, \App\Models\BlockExercise>  $blockExercises
     */
    private function populateStrengthSets(Collection $blockExercises, ParsedActivity $parsed): void
    {
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
     * @param  Collection<int, \App\Models\BlockExercise>  $blockExercises
     */
    private function populateCardioSets(Collection $blockExercises, ParsedActivity $parsed): void
    {
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
}
