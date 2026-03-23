<?php

declare(strict_types=1);

namespace App\Support\Fit\Decode;

use App\DataTransferObjects\Fit\ParsedActivity;
use App\DataTransferObjects\Fit\ParsedSet;
use App\DataTransferObjects\Workout\BlockData;
use App\DataTransferObjects\Workout\CardioExerciseData;
use App\DataTransferObjects\Workout\ExerciseData;
use App\DataTransferObjects\Workout\SectionData;
use App\DataTransferObjects\Workout\StrengthExerciseData;
use App\Enums\Workout\BlockType;
use App\Enums\Workout\ExerciseType;
use App\Models\Exercise;
use Illuminate\Support\Collection;

class BuildWorkoutFromActivity
{
    public function __construct(private MatchGarminExercise $matcher) {}

    /**
     * @return array{sections: Collection<int, SectionData>, matched: list<string>, unmatched: list<string>, warnings: list<string>}
     */
    public function execute(ParsedActivity $activity): array
    {
        $matched = [];
        $unmatched = [];
        $warnings = [];

        if (count($activity->sets) > 0) {
            $result = $this->buildStrengthSections($activity, $matched, $unmatched, $warnings);
        } else {
            $result = $this->buildCardioSections($activity);
        }

        return [
            'sections' => $result,
            'matched' => $matched,
            'unmatched' => $unmatched,
            'warnings' => $warnings,
        ];
    }

    /**
     * @param  list<string>  $matched
     * @param  list<string>  $unmatched
     * @param  list<string>  $warnings
     * @return Collection<int, SectionData>
     */
    private function buildStrengthSections(
        ParsedActivity $activity,
        array &$matched,
        array &$unmatched,
        array &$warnings,
    ): Collection {
        $titleMap = $this->buildTitleMap($activity);
        $activeSets = collect($activity->sets)->filter->isActive();
        $groups = $this->groupSetsByExercise($activeSets);

        $blocks = collect();
        $blockOrder = 0;

        foreach ($groups as $group) {
            $sets = $group['sets'];
            $category = $group['category'];
            $name = $group['name'];

            $displayName = $titleMap["{$category}:{$name}"] ?? "Exercise {$category}/{$name}";
            $exercise = $this->matchExercise($category, $name, $displayName, $matched, $unmatched, $warnings);

            $reps = collect($sets)->pluck('repetitions')->filter()->values();
            $weights = collect($sets)->pluck('weight')->filter()->values();

            $exerciseData = new ExerciseData(
                name: $exercise?->name ?? $displayName,
                order: 0,
                type: ExerciseType::Strength,
                exerciseable: new StrengthExerciseData(
                    targetSets: count($sets),
                    targetRepsMin: $reps->isNotEmpty() ? (int) $reps->min() : null,
                    targetRepsMax: $reps->isNotEmpty() ? (int) $reps->max() : null,
                    targetWeight: $weights->isNotEmpty() ? (float) $weights->max() : null,
                ),
                exerciseId: $exercise?->id,
            );

            $blocks->push(new BlockData(
                blockType: BlockType::StraightSets,
                order: $blockOrder,
                exercises: collect([$exerciseData]),
            ));

            $blockOrder++;
        }

        return collect([
            new SectionData(
                name: 'Workout',
                order: 0,
                blocks: $blocks,
            ),
        ]);
    }

    /**
     * @return Collection<int, SectionData>
     */
    private function buildCardioSections(ParsedActivity $activity): Collection
    {
        $exerciseData = new ExerciseData(
            name: $activity->session->workoutName ?? 'Activity',
            order: 0,
            type: ExerciseType::Cardio,
            exerciseable: new CardioExerciseData(
                targetDuration: $activity->session->totalElapsedTime,
                targetDistance: $activity->session->totalDistance !== null ? (float) $activity->session->totalDistance : null,
            ),
        );

        $block = new BlockData(
            blockType: BlockType::DistanceDuration,
            order: 0,
            exercises: collect([$exerciseData]),
        );

        return collect([
            new SectionData(
                name: 'Workout',
                order: 0,
                blocks: collect([$block]),
            ),
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
     * Group consecutive active sets by exercise_category+exercise_name.
     *
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

    /**
     * @param  list<string>  $matched
     * @param  list<string>  $unmatched
     * @param  list<string>  $warnings
     */
    private function matchExercise(
        int $category,
        int $name,
        string $displayName,
        array &$matched,
        array &$unmatched,
        array &$warnings,
    ): ?Exercise {
        $exercise = $this->matcher->match($category, $name);

        if ($exercise !== null) {
            $matched[] = $exercise->name;
        } else {
            $unmatched[] = $displayName;
            $warnings[] = "Could not match exercise: {$displayName}";
        }

        return $exercise;
    }
}
