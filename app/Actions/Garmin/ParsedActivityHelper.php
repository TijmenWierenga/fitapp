<?php

declare(strict_types=1);

namespace App\Actions\Garmin;

use App\DataTransferObjects\Fit\DetectedBlock;
use App\DataTransferObjects\Fit\ExerciseGroup;
use App\DataTransferObjects\Fit\ParsedActivity;
use App\DataTransferObjects\Fit\ParsedSet;
use App\Enums\Fit\DetectedBlockType;
use Illuminate\Support\Collection;

class ParsedActivityHelper
{
    /**
     * Group consecutive active sets by exercise_category+exercise_name.
     *
     * @param  Collection<int, ParsedSet>  $sets
     * @return list<ExerciseGroup>
     */
    public static function groupSetsByExercise(Collection $sets): array
    {
        $groups = [];
        $currentKey = null;
        $currentCategory = 0;
        $currentName = 0;
        $currentSets = [];

        foreach ($sets as $set) {
            $hasIdentification = $set->exerciseCategory !== null && $set->exerciseName !== null;
            $category = $set->exerciseCategory ?? 0;
            $name = $set->exerciseName ?? 0;

            $key = $hasIdentification
                ? "{$category}:{$name}"
                : 'weight:'.($set->weight ?? 0);

            if ($currentKey !== null && $currentKey !== $key) {
                $groups[] = new ExerciseGroup($currentKey, $currentCategory, $currentName, $currentSets);
                $currentSets = [];
            }

            $currentKey = $key;
            $currentCategory = $category;
            $currentName = $name;
            $currentSets[] = $set;
        }

        if ($currentKey !== null) {
            $groups[] = new ExerciseGroup($currentKey, $currentCategory, $currentName, $currentSets);
        }

        return $groups;
    }

    /**
     * Build a lookup map of exercise category:name → display name from exercise titles.
     *
     * @return array<string, string>
     */
    public static function buildTitleMap(ParsedActivity $activity): array
    {
        $map = [];

        foreach ($activity->exerciseTitles as $title) {
            $map["{$title->exerciseCategory}:{$title->exerciseName}"] = $title->displayName;
        }

        return $map;
    }

    /**
     * Detect superset patterns in grouped exercise sets.
     *
     * Takes the output of groupSetsByExercise and identifies repeating exercise cycles
     * (A-B-A-B becomes a superset of A+B). Non-repeating groups stay as straight sets.
     *
     * @param  list<ExerciseGroup>  $groups
     * @return list<DetectedBlock>
     */
    public static function detectBlocks(array $groups): array
    {
        $blocks = [];
        $i = 0;
        $groupCount = count($groups);

        while ($i < $groupCount) {
            $seenKeys = [];
            $cycle = [];
            $j = $i;

            while ($j < $groupCount && ! in_array($groups[$j]->key, $seenKeys, true)) {
                $seenKeys[] = $groups[$j]->key;
                $cycle[] = $groups[$j]->key;
                $j++;
            }

            $cycleLength = count($cycle);

            if ($cycleLength > 1 && $j < $groupCount && $groups[$j]->key === $cycle[0]) {
                // Found a superset cycle — collect all rounds that match the pattern
                $end = $i;

                while ($end < $groupCount && $groups[$end]->key === $cycle[($end - $i) % $cycleLength]) {
                    $end++;
                }

                $exercises = [];

                for ($k = $i; $k < $end; $k++) {
                    $group = $groups[$k];

                    if (! isset($exercises[$group->key])) {
                        $exercises[$group->key] = new ExerciseGroup($group->key, $group->category, $group->name, []);
                    }

                    $exercises[$group->key] = new ExerciseGroup(
                        $group->key,
                        $group->category,
                        $group->name,
                        [...$exercises[$group->key]->sets, ...$group->sets],
                    );
                }

                $blocks[] = new DetectedBlock(DetectedBlockType::Superset, $exercises);
                $i = $end;
            } else {
                // No repeat — each group is a straight set
                for ($k = $i; $k < $j; $k++) {
                    $group = $groups[$k];
                    $blocks[] = new DetectedBlock(
                        DetectedBlockType::Straight,
                        [$group->key => $group],
                    );
                }

                $i = $j;
            }
        }

        return $blocks;
    }
}
