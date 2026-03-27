<?php

declare(strict_types=1);

namespace App\Actions\Garmin;

use App\DataTransferObjects\Fit\ParsedActivity;
use App\DataTransferObjects\Fit\ParsedLap;
use App\Models\BlockExercise;
use App\Models\ExerciseSet;
use App\Models\StrengthExercise;
use App\Models\Workout;
use App\Support\Workout\PaceConverter;
use Illuminate\Support\Collection;

class MergeActualsIntoWorkout
{
    public function __construct(private MatchGarminExercise $matcher) {}

    /**
     * @param  array<int, int>  $exerciseMappings  Map of exercise group index → Exercise model ID
     * @return array{matched: list<string>, unmatched: list<string>, warnings: list<string>}
     */
    public function execute(Workout $workout, ParsedActivity $activity, array $exerciseMappings = []): array
    {
        $matched = [];
        $unmatched = [];
        $warnings = [];

        $workout->loadMissing('sections.blocks.exercises.exerciseable');

        $hasSets = count($activity->sets) > 0;
        $hasCardioLaps = collect($activity->laps)->contains(fn ($lap) => ($lap->totalDistance ?? 0) > 0);

        if ($hasSets) {
            $this->mergeStrength($workout, $activity, $matched, $unmatched, $warnings, $exerciseMappings);
        }

        if ($hasCardioLaps || ! $hasSets) {
            $this->mergeCardio($workout, $activity, $matched, $warnings);
        }

        return [
            'matched' => $matched,
            'unmatched' => $unmatched,
            'warnings' => $warnings,
        ];
    }

    /**
     * @param  list<string>  $matched
     * @param  list<string>  $unmatched
     * @param  list<string>  $warnings
     * @param  array<int, int>  $exerciseMappings
     */
    private function mergeStrength(
        Workout $workout,
        ParsedActivity $activity,
        array &$matched,
        array &$unmatched,
        array &$warnings,
        array $exerciseMappings = [],
    ): void {
        $titleMap = ParsedActivityHelper::buildTitleMap($activity);
        $activeSets = collect($activity->sets)->filter->isActive();
        $groups = ParsedActivityHelper::groupSetsByExercise($activeSets);
        $detectedBlocks = ParsedActivityHelper::detectBlocks($groups);

        $plannedExercises = $this->getPlannedBlockExercises($workout);
        $globalExerciseIndex = 0;

        foreach ($detectedBlocks as $detected) {
            foreach ($detected['exercises'] as $key => $exerciseInfo) {
                // Check user-provided mapping first
                $mappedExercise = isset($exerciseMappings[$globalExerciseIndex])
                    ? \App\Models\Exercise::find($exerciseMappings[$globalExerciseIndex])
                    : null;

                if ($mappedExercise) {
                    $exercise = $mappedExercise;
                } else {
                    $exercise = $this->matcher->match($exerciseInfo['category'], $exerciseInfo['name']);
                }

                $blockExercise = null;

                if ($exercise !== null) {
                    $blockExercise = $plannedExercises->first(fn (BlockExercise $be) => $be->exercise_id === $exercise->id);
                }

                if ($blockExercise !== null) {
                    $matched[] = $exercise->name;
                } else {
                    $displayName = $exercise?->name
                        ?? $titleMap["{$exerciseInfo['category']}:{$exerciseInfo['name']}"]
                        ?? 'Exercise '.($globalExerciseIndex + 1);
                    $unmatched[] = $displayName;

                    $blockExercise = $this->appendBlockExercise($workout, $displayName, $exercise);
                    $warnings[] = "Extra exercise added: {$displayName}";
                }

                $this->createStrengthSets($blockExercise, $exerciseInfo['sets']);
                $globalExerciseIndex++;
            }
        }
    }

    /**
     * @param  list<string>  $matched
     * @param  list<string>  $warnings
     */
    private function mergeCardio(
        Workout $workout,
        ParsedActivity $activity,
        array &$matched,
        array &$warnings,
    ): void {
        $blockExercises = $this->getPlannedBlockExercises($workout);
        $laps = collect($activity->laps);

        if ($blockExercises->isEmpty()) {
            $warnings[] = 'No planned exercises to merge laps into.';

            return;
        }

        $primaryExercise = $blockExercises->first();
        $matched[] = $primaryExercise->name;

        foreach ($laps as $index => $lap) {
            $this->createCardioSet($primaryExercise, $index + 1, $lap);
        }
    }

    /**
     * @param  list<ParsedSet>  $sets
     */
    private function createStrengthSets(BlockExercise $blockExercise, array $sets): void
    {
        foreach ($sets as $index => $set) {
            ExerciseSet::create([
                'block_exercise_id' => $blockExercise->id,
                'set_number' => $index + 1,
                'reps' => $set->repetitions,
                'weight' => $set->weight,
                'set_duration' => $set->duration,
            ]);
        }
    }

    private function createCardioSet(BlockExercise $blockExercise, int $setNumber, ParsedLap $lap): void
    {
        ExerciseSet::create([
            'block_exercise_id' => $blockExercise->id,
            'set_number' => $setNumber,
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

    /**
     * @return Collection<int, BlockExercise>
     */
    private function getPlannedBlockExercises(Workout $workout): Collection
    {
        return $workout->sections
            ->flatMap(fn ($section) => $section->blocks)
            ->flatMap(fn ($block) => $block->exercises);
    }

    private function appendBlockExercise(Workout $workout, string $name, ?\App\Models\Exercise $exercise): BlockExercise
    {
        $lastSection = $workout->sections->last();

        if ($lastSection === null) {
            $lastSection = $workout->sections()->create([
                'name' => 'Workout',
                'order' => 0,
            ]);
        }

        $lastBlock = $lastSection->blocks->last();

        if ($lastBlock === null) {
            $lastBlock = $lastSection->blocks()->create([
                'block_type' => \App\Enums\Workout\BlockType::StraightSets,
                'order' => 0,
            ]);
        }

        $maxOrder = $lastBlock->exercises()->max('order') ?? -1;

        $exerciseable = StrengthExercise::create([]);

        return $lastBlock->exercises()->create([
            'name' => $name,
            'order' => $maxOrder + 1,
            'exercise_id' => $exercise?->id,
            'exerciseable_type' => $exerciseable->getMorphClass(),
            'exerciseable_id' => $exerciseable->getKey(),
        ]);
    }
}
