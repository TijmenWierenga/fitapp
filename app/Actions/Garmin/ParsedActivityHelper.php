<?php

declare(strict_types=1);

namespace App\Actions\Garmin;

use App\DataTransferObjects\Fit\ParsedActivity;
use App\DataTransferObjects\Fit\ParsedSet;
use Illuminate\Support\Collection;

class ParsedActivityHelper
{
    /**
     * Group consecutive active sets by exercise_category+exercise_name.
     *
     * @param  Collection<int, ParsedSet>  $sets
     * @return list<array{key: string, category: int, name: int, sets: list<ParsedSet>}>
     */
    public static function groupSetsByExercise(Collection $sets): array
    {
        $groups = [];
        $currentGroup = null;

        foreach ($sets as $set) {
            $hasIdentification = $set->exerciseCategory !== null && $set->exerciseName !== null;
            $category = $set->exerciseCategory ?? 0;
            $name = $set->exerciseName ?? 0;

            $key = $hasIdentification
                ? "{$category}:{$name}"
                : 'weight:'.($set->weight ?? 0);

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
     * Returns a list of "detected blocks", each with:
     * - type: 'superset' or 'straight'
     * - exercises: array keyed by exercise key, each containing merged sets and metadata
     *
     * @param  list<array{key: string, category: int, name: int, sets: list<ParsedSet>}>  $groups
     * @return list<array{type: 'superset'|'straight', exercises: array<string, array{category: int, name: int, sets: list<ParsedSet>}>}>
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

            while ($j < $groupCount && ! in_array($groups[$j]['key'], $seenKeys, true)) {
                $seenKeys[] = $groups[$j]['key'];
                $cycle[] = $groups[$j]['key'];
                $j++;
            }

            $cycleLength = count($cycle);

            if ($cycleLength > 1 && $j < $groupCount && $groups[$j]['key'] === $cycle[0]) {
                // Found a superset cycle — collect all rounds that match the pattern
                $end = $i;

                while ($end < $groupCount && $groups[$end]['key'] === $cycle[($end - $i) % $cycleLength]) {
                    $end++;
                }

                $exercises = [];

                for ($k = $i; $k < $end; $k++) {
                    $group = $groups[$k];
                    $key = $group['key'];

                    if (! isset($exercises[$key])) {
                        $exercises[$key] = [
                            'category' => $group['category'],
                            'name' => $group['name'],
                            'sets' => [],
                        ];
                    }

                    $exercises[$key]['sets'] = [...$exercises[$key]['sets'], ...$group['sets']];
                }

                $blocks[] = ['type' => 'superset', 'exercises' => $exercises];
                $i = $end;
            } else {
                // No repeat — each group is a straight set
                for ($k = $i; $k < $j; $k++) {
                    $group = $groups[$k];
                    $blocks[] = [
                        'type' => 'straight',
                        'exercises' => [
                            $group['key'] => [
                                'category' => $group['category'],
                                'name' => $group['name'],
                                'sets' => $group['sets'],
                            ],
                        ],
                    ];
                }

                $i = $j;
            }
        }

        return $blocks;
    }
}
