<?php

declare(strict_types=1);

namespace App\Domain\Workload\Calculators;

use App\Domain\Workload\Enums\Trend;
use App\Domain\Workload\Results\MuscleGroupVolumeResult;
use App\Domain\Workload\ValueObjects\DateRange;
use App\Domain\Workload\ValueObjects\MuscleGroupMapping;
use App\Domain\Workload\ValueObjects\PerformedExercise;

class MuscleGroupVolumeCalculator
{
    private const string STRENGTH_TYPE = 'strength';

    /**
     * @param  array<PerformedExercise>  $exercises
     * @param  array<DateRange>  $weekRanges
     * @return array<MuscleGroupVolumeResult>
     */
    public function calculate(array $exercises, array $weekRanges): array
    {
        $strengthExercises = array_filter(
            $exercises,
            fn (PerformedExercise $e): bool => $e->exerciseType === self::STRENGTH_TYPE,
        );

        if (count($strengthExercises) === 0 || count($weekRanges) === 0) {
            return [];
        }

        $currentWeek = $weekRanges[0];
        $allWeeks = $weekRanges;

        /** @var array<int, array{mapping: MuscleGroupMapping, weekSets: array<int, float>}> $muscleGroupData */
        $muscleGroupData = [];

        foreach ($strengthExercises as $exercise) {
            foreach ($exercise->muscleGroups as $mapping) {
                if (! isset($muscleGroupData[$mapping->muscleGroupId])) {
                    $muscleGroupData[$mapping->muscleGroupId] = [
                        'mapping' => $mapping,
                        'weekSets' => array_fill(0, count($allWeeks), 0.0),
                    ];
                }

                $effectiveSets = $exercise->sets * $mapping->loadFactor;

                foreach ($allWeeks as $weekIndex => $week) {
                    if ($week->contains($exercise->completedAt)) {
                        $muscleGroupData[$mapping->muscleGroupId]['weekSets'][$weekIndex] += $effectiveSets;

                        break;
                    }
                }
            }
        }

        $results = [];

        foreach ($muscleGroupData as $data) {
            $mapping = $data['mapping'];
            $weekSets = $data['weekSets'];
            $currentWeekSets = $weekSets[0];

            $weekCount = count($weekSets);
            $average = $weekCount > 0 ? array_sum($weekSets) / $weekCount : 0.0;

            $trend = $this->determineTrend($currentWeekSets, $average);

            $results[] = new MuscleGroupVolumeResult(
                muscleGroupId: $mapping->muscleGroupId,
                name: $mapping->name,
                label: $mapping->label,
                bodyPart: $mapping->bodyPart,
                currentWeekSets: round($currentWeekSets, 1),
                fourWeekAverageSets: round($average, 1),
                trend: $trend,
            );
        }

        return $results;
    }

    private function determineTrend(float $current, float $average): Trend
    {
        if ($average === 0.0) {
            return $current > 0 ? Trend::Increasing : Trend::Stable;
        }

        if ($current > $average * 1.1) {
            return Trend::Increasing;
        }

        if ($current < $average * 0.9) {
            return Trend::Decreasing;
        }

        return Trend::Stable;
    }
}
