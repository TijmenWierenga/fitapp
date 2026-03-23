<?php

declare(strict_types=1);

namespace App\Support\Fit\Decode;

use App\DataTransferObjects\Fit\ParsedActivity;
use App\DataTransferObjects\Fit\ParsedLap;
use App\DataTransferObjects\Fit\ParsedSet;
use App\Models\BlockExercise;
use App\Models\ExerciseSet;
use App\Models\StrengthExercise;
use App\Models\Workout;
use Illuminate\Support\Collection;

class MergeActualsIntoWorkout
{
    public function __construct(private MatchGarminExercise $matcher) {}

    /**
     * @return array{matched: list<string>, unmatched: list<string>, warnings: list<string>}
     */
    public function execute(Workout $workout, ParsedActivity $activity): array
    {
        $matched = [];
        $unmatched = [];
        $warnings = [];

        $workout->loadMissing('sections.blocks.exercises.exerciseable');

        if (count($activity->sets) > 0) {
            $this->mergeStrength($workout, $activity, $matched, $unmatched, $warnings);
        } else {
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
     */
    private function mergeStrength(
        Workout $workout,
        ParsedActivity $activity,
        array &$matched,
        array &$unmatched,
        array &$warnings,
    ): void {
        $titleMap = $this->buildTitleMap($activity);
        $activeSets = collect($activity->sets)->filter->isActive();
        $groups = $this->groupSetsByExercise($activeSets);

        $plannedExercises = $this->getPlannedBlockExercises($workout);

        foreach ($groups as $group) {
            $category = $group['category'];
            $name = $group['name'];
            $exercise = $this->matcher->match($category, $name);

            $blockExercise = null;

            if ($exercise !== null) {
                $blockExercise = $plannedExercises->first(fn (BlockExercise $be) => $be->exercise_id === $exercise->id);
            }

            if ($blockExercise !== null) {
                $matched[] = $exercise->name;
            } else {
                $displayName = $titleMap["{$category}:{$name}"] ?? "Exercise {$category}/{$name}";
                $unmatched[] = $displayName;

                $blockExercise = $this->appendBlockExercise($workout, $displayName, $exercise);
                $warnings[] = "Extra exercise added: {$displayName}";
            }

            $this->createStrengthSets($blockExercise, $group['sets']);
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
        $avgPace = null;

        if ($lap->avgSpeed !== null && $lap->avgSpeed > 0) {
            $avgPace = (int) round(1_000_000 / $lap->avgSpeed);
        }

        ExerciseSet::create([
            'block_exercise_id' => $blockExercise->id,
            'set_number' => $setNumber,
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

    /**
     * @return array<string, string>
     */
    private function buildTitleMap(ParsedActivity $activity): array
    {
        $map = [];

        foreach ($activity->exerciseTitles as $title) {
            $map["{$title->exerciseCategory}:{$title->exerciseName}"] = $title->displayName;
        }

        return $map;
    }

    /**
     * @param  Collection<int, ParsedSet>  $sets
     * @return list<array{category: int, name: int, sets: list<ParsedSet>}>
     */
    private function groupSetsByExercise(Collection $sets): array
    {
        $groups = [];
        $currentGroup = null;

        foreach ($sets as $set) {
            $category = $set->exerciseCategory ?? 0;
            $name = $set->exerciseName ?? 0;
            $key = "{$category}:{$name}";

            if ($currentGroup === null || $currentGroup['key'] !== $key) {
                if ($currentGroup !== null) {
                    $groups[] = $currentGroup;
                }
                $currentGroup = [
                    'key' => $key,
                    'category' => $category,
                    'name' => $name,
                    'sets' => [],
                ];
            }

            $currentGroup['sets'][] = $set;
        }

        if ($currentGroup !== null) {
            $groups[] = $currentGroup;
        }

        return $groups;
    }
}
