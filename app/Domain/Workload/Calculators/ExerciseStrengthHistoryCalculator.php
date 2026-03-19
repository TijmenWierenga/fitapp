<?php

declare(strict_types=1);

namespace App\Domain\Workload\Calculators;

use App\Domain\Workload\Results\ExerciseStrengthHistoryPoint;
use App\Domain\Workload\Results\ExerciseStrengthHistoryResult;
use App\Domain\Workload\ValueObjects\StrengthRecord;
use DateTimeImmutable;

class ExerciseStrengthHistoryCalculator
{
    /**
     * @param  array<StrengthRecord>  $records
     */
    public function calculate(array $records, int $exerciseId, string $exerciseName): ExerciseStrengthHistoryResult
    {
        if (empty($records)) {
            return new ExerciseStrengthHistoryResult($exerciseId, $exerciseName, []);
        }

        $grouped = [];

        foreach ($records as $record) {
            $dateKey = $record->performedAt->format('Y-m-d');
            $grouped[$dateKey][] = $record;
        }

        ksort($grouped);

        $points = [];

        foreach ($grouped as $dateKey => $dayRecords) {
            $maxWeight = 0.0;
            $volume = 0.0;
            $maxE1RM = 0.0;

            foreach ($dayRecords as $record) {
                $maxWeight = max($maxWeight, $record->weight);
                $volume += $record->sets * $record->reps * $record->weight;
                $maxE1RM = max($maxE1RM, $record->estimated1RM());
            }

            $points[] = new ExerciseStrengthHistoryPoint(
                date: new DateTimeImmutable($dateKey),
                maxWeight: $maxWeight,
                volume: $volume,
                estimated1RM: $maxE1RM,
            );
        }

        return new ExerciseStrengthHistoryResult($exerciseId, $exerciseName, $points);
    }
}
